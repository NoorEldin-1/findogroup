<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OperatingWorkflow
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier) {}

    public function fail(string $ar, string $en): never
    {
        throw ValidationException::withMessages(['workflow' => app()->getLocale() === 'en' ? $en : $ar]);
    }

    public function event(?object $record, string $action, string $reason, ?array $before, ?array $after, ?int $userId = null): void
    {
        DB::table('operating_events')->insert(['record_id' => $record?->id, 'user_id' => $userId ?? auth()->id(), 'action' => $action, 'reason' => $reason, 'before' => $before ? json_encode($before, JSON_UNESCAPED_UNICODE) : null, 'after' => $after ? json_encode($after, JSON_UNESCAPED_UNICODE) : null, 'created_at' => now()]);
    }

    /**
     * Reminder markers are system bookkeeping: they are audited but do not bump the record version, so they never conflict with a user editing the same record.
     *
     * Only the marker key is written (JSON path update), so a concurrent user edit to the same record is never overwritten.
     */
    public function mark(object $record, string $key, string $value, string $reason): void
    {
        DB::table('operating_records')->where('id', $record->id)->update(['data->'.$key => $value]);
        $this->event($record, 'reminder', $reason, null, [$key => $value]);
    }

    public function save(string $type, Request $request, ?int $id = null): object
    {
        $user = $request->user();
        abort_unless($this->access->can($user, $type, 'write'), 403);
        $entity = $this->catalog->entity($type);
        $existing = $id ? $this->access->record($user, $id, 'write') : null;
        abort_if($existing && $existing->type !== $type, 404);
        if ($existing && $existing->source !== 'findo') {
            $this->fail('هذه الحقول يملكها Jira. عدّلها في المصدر ثم أعد المزامنة.', 'Jira owns this record. Update the source, then sync.');
        }
        abort_if(! $existing && ! $this->access->canCreate($user, $type), 403);
        $limitedEmployee = ! in_array($user->role, ['owner', 'hr', 'delivery'], true);
        if (! $existing && $limitedEmployee && in_array($type, ['evaluations', 'goals', 'improvement_plans'], true)) {
            abort(403);
        }
        $fields = $this->access->fields($user, $type, $existing);
        if ($type === 'tasks' && $user->role === 'developer') {
            $fields = array_filter($fields, fn (array $field): bool => in_array($field['key'], ['remaining_hours', 'implementation_url', 'build_version', 'closure_reason'], true));
        }
        $rules = ['title' => 'required|string|max:500', 'version' => $existing ? 'required|integer|min:1' : 'nullable|integer', 'reason' => 'required|string|max:2000'];
        foreach ($fields as $field) {
            if ($field['type'] === 'lines') {
                $rules['data.'.$field['key']] = 'nullable|array|max:100';
                foreach (['description' => 'nullable|string|max:500', 'kind' => 'nullable|in:product,service', 'quantity' => 'nullable|numeric|min:0|max:1000000', 'unit_price' => 'nullable|numeric|min:0|max:1000000000', 'discount' => 'nullable|numeric|min:0|max:100', 'tax' => 'nullable|numeric|min:0|max:100'] as $column => $columnRule) {
                    $rules['data.'.$field['key'].'.*.'.$column] = $columnRule;
                }

                continue;
            }
            $rule = ($field['required'] ? 'required' : 'nullable').'|';
            $rule .= match ($field['type']) {
                'relation' => 'integer|exists:operating_records,id',
                'user' => 'integer|exists:users,id',
                'number' => 'numeric|min:0|max:1000000000',
                'date', 'datetime-local' => 'date',
                'time' => 'date_format:H:i',
                'checkbox' => 'boolean',
                'select' => 'in:'.implode(',', array_keys($field['options'])),
                'url' => 'url:http,https|max:2048',
                'email' => 'email|max:250',
                default => 'string|max:20000',
            };
            $rules['data.'.$field['key']] = $rule;
        }
        $validated = $request->validate($rules);
        $data = $existing ? $this->catalog->data($existing) : [];
        foreach ($fields as $field) {
            if ($field['type'] === 'lines') {
                $data[$field['key']] = app(OperatingQuotes::class)->normalizeLines($validated['data'][$field['key']] ?? []);

                continue;
            }
            $value = $validated['data'][$field['key']] ?? ($field['type'] === 'checkbox' ? false : null);
            if ($field['type'] === 'relation' && $value) {
                $target = DB::table('operating_records')->find($value);
                if ($target->type !== $field['entity'] || $target->status === 'archived') {
                    $this->fail('العلاقة لا تشير إلى سجل صالح من النوع المطلوب.', 'The relationship must reference an active record of the required type.');
                }
                $this->access->record($user, $target->id);
                if ($field['entity'] === 'employees' && $limitedEmployee && in_array($type, ['leave', 'time_entries', 'data_batches'], true) && $value != $this->access->employeeId($user)) {
                    abort(403);
                }
            }
            $data[$field['key']] = $value;
        }
        if (($entity['private'] ?? false) && $user->role === 'delivery' && ! empty($data['assignee_id'])) {
            $targetEmployee = DB::table('operating_records')->find($data['assignee_id']);
            $employeeData = $this->catalog->data($targetEmployee);
            abort_unless($targetEmployee->id === $this->access->employeeId($user) || ($employeeData['manager_id'] ?? null) == $this->access->employeeId($user), 403);
        }
        foreach ($entity['fields'] as $field) {
            if ($field['type'] === 'relation' && ! empty($data[$field['key']]) && ! in_array($field['entity'], ['employees', 'clients', 'projects'], true)) {
                $related = $this->catalog->data(DB::table('operating_records')->find($data[$field['key']]));
                if (! empty($data['project_id']) && ! empty($related['project_id']) && $data['project_id'] != $related['project_id']) {
                    $this->fail('لا يمكن ربط سجل من مشروع مختلف.', 'Related records must belong to the same project.');
                }
            }
        }
        if ($type === 'tasks' && ! empty($data['assignee_id']) && $data['assignee_id'] == ($data['reviewer_id'] ?? null)) {
            $this->fail('مراجع الكود يجب أن يكون مستقلًا عن المنفذ.', 'The code reviewer must be independent of the assignee.');
        }
        if ($type === 'employees' && ! empty($data['user_id']) && DB::table('operating_records')->where('type', 'employees')->where('data->user_id', $data['user_id'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
            $this->fail('الحساب مرتبط بالفعل بموظف آخر.', 'This account is already linked to another employee.');
        }
        if ($type === 'employees' && ! in_array($user->role, ['owner', 'hr'], true) && (int) ($data['manager_id'] ?? 0) !== (int) (($existing ? $this->catalog->data($existing) : [])['manager_id'] ?? 0)) {
            $this->fail('تعيين المدير المباشر للموظف من صلاحية الإدارة أو HR فقط.', 'Only owner or HR can assign an employee’s direct manager.');
        }
        if ($type === 'employees' && $id && ($data['manager_id'] ?? null)) {
            $seen = [$id];
            $manager = $data['manager_id'];
            while ($manager) {
                if (in_array((int) $manager, $seen, true)) {
                    $this->fail('الهيكل الإداري يحتوي دائرة.', 'The reporting structure contains a cycle.');
                }
                $seen[] = (int) $manager;
                $manager = $this->catalog->data(DB::table('operating_records')->find($manager))['manager_id'] ?? null;
            }
        }
        if ($type === 'scopes' && DB::table('operating_records')->where('type', 'scopes')->where('data->project_id', $data['project_id'])->where('data->scope_version', $data['scope_version'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
            $this->fail('رقم إصدار النطاق مستخدم لهذا المشروع.', 'This project already has that scope version.');
        }
        if ($type === 'tasks' && ! empty($data['dependency_id'])) {
            $seen = $id ? [$id] : [];
            $dependency = $data['dependency_id'];
            while ($dependency) {
                if (in_array((int) $dependency, $seen, true)) {
                    $this->fail('التبعيات تحتوي دائرة.', 'Task dependencies contain a cycle.');
                }
                $seen[] = (int) $dependency;
                $dependency = $this->catalog->data(DB::table('operating_records')->find($dependency))['dependency_id'] ?? null;
            }
        }
        if ($type === 'receipts') {
            $invoice = DB::table('operating_records')->find($data['invoice_id']);
            $invoiceData = $this->catalog->data($invoice);
            $received = DB::table('operating_records')->where('type', 'receipts')->where('data->invoice_id', $invoice->id)->where('status', '!=', 'archived')->when($id, fn ($query) => $query->where('id', '!=', $id))->get()->sum(fn (object $row): float => (float) ($this->catalog->data($row)['amount'] ?? 0));
            if ($received + $data['amount'] > ($invoiceData['amount'] ?? 0)) {
                $this->fail('التحصيل يتجاوز مبلغ الفاتورة.', 'Receipts cannot exceed the invoice amount.');
            }
        }
        if ($type === 'budgets' && ($data['target_margin'] ?? 0) >= 100) {
            $this->fail('الهامش المستهدف يجب أن يكون أقل من 100%.', 'Target margin must be below 100%.');
        }
        foreach (['target_margin', 'weight', 'probability', 'allocation_percent'] as $percentage) {
            if (isset($data[$percentage]) && $data[$percentage] > 100) {
                $this->fail('النسبة لا تتجاوز 100%.', 'Percentages must not exceed 100%.');
            }
        }
        if ($type === 'capacity') {
            if (($data['planned_hours'] ?? 0) + ($data['meetings_hours'] ?? 0) + ($data['support_reserve'] ?? 0) > ($data['contract_hours'] ?? 0)) {
                $this->fail('الخطة تتجاوز السعة المتاحة بعد المراجعات واحتياطي الدعم.', 'Planned work exceeds capacity after meetings and support reserve.');
            }
        }
        if ($type === 'data_batches' && ($data['valid_rows'] ?? 0) > ($data['sample_size'] ?? 0)) {
            $this->fail('السجلات الصحيحة تتجاوز حجم العينة.', 'Valid rows cannot exceed the sample size.');
        }
        if ($type === 'opportunities') {
            if (empty($data['client_id']) && empty($data['lead_company']) && empty($data['lead_phone'])) {
                $this->fail('اربط الفرصة بعميل أو سجّل اسم الشركة أو رقم الهاتف.', 'Link a client or enter the lead company or phone.');
            }
            $this->limitAssignment($user, $data, $existing);
            $crm = app(OperatingCrm::class);
            $data['phone_key'] = $crm->phoneKey($data['lead_phone'] ?? null);
            if ($data['phone_key'] && ($duplicate = $crm->phoneOwner($data['phone_key'], $existing?->id))) {
                $since = substr((string) $duplicate->created_at, 0, 10);
                $this->fail('هذا الرقم مسجل بالفعل في الفرصة "'.$duplicate->title.'" منذ '.$since.'.', 'This phone is already registered on lead "'.$duplicate->title.'" since '.$since.'.');
            }
            if (! $existing) {
                if (empty($data['assignee_id'])) {
                    $data['assignee_id'] = $crm->isManager($user) ? $crm->nextAssignee() : $this->access->employeeId($user);
                }
                $data['sla_due_at'] = $crm->slaDueAt(now());
            }
        }
        if ($type === 'activities') {
            if (empty($data['client_id']) && empty($data['opportunity_id'])) {
                $this->fail('اربط المتابعة بعميل أو فرصة.', 'Link the follow-up to a client or a lead.');
            }
            if (! empty($data['opportunity_id']) && empty($data['client_id'])) {
                $data['client_id'] = $this->catalog->data(DB::table('operating_records')->find($data['opportunity_id']))['client_id'] ?? null;
            }
            $this->limitAssignment($user, $data, $existing);
            if ($existing && ($this->catalog->data($existing)['due_at'] ?? null) !== $data['due_at']) {
                unset($data['reminded_at'], $data['overdue_alerted_at']);
            }
        }
        if ($type === 'contacts' && ! empty($data['is_primary']) && DB::table('operating_records')->where('type', 'contacts')->where('status', '!=', 'archived')->where('data->client_id', (int) $data['client_id'])->when($id, fn ($query) => $query->where('id', '!=', $id))->get()->contains(fn (object $contact): bool => ! empty($this->catalog->data($contact)['is_primary']))) {
            $this->fail('للعميل جهة اتصال أساسية بالفعل. ألغِ تحديدها أولًا.', 'This client already has a primary contact. Unset it first.');
        }
        if ($type === 'project_members') {
            if (! empty($data['end_date']) && ! empty($data['start_date']) && $data['end_date'] < $data['start_date']) {
                $this->fail('تاريخ النهاية قبل تاريخ البداية.', 'The end date is before the start date.');
            }
            if (DB::table('operating_records')->where('type', 'project_members')->where('status', '!=', 'archived')->where('data->project_id', (int) $data['project_id'])->where('data->assignee_id', (int) $data['assignee_id'])->where('data->member_role', $data['member_role'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('الموظف مسجل بنفس الدور في هذا المشروع.', 'This employee already has that role on the project.');
            }
        }
        if ($type === 'sprints' && $data['end_date'] < $data['start_date']) {
            $this->fail('نهاية السبرنت قبل بدايته.', 'The sprint ends before it starts.');
        }
        if ($type === 'campaigns' && ! empty($data['start_date']) && ! empty($data['end_date']) && $data['end_date'] < $data['start_date']) {
            $this->fail('نهاية الحملة قبل بدايتها.', 'The campaign ends before it starts.');
        }
        if ($type === 'team_tasks') {
            $this->limitAssignment($user, $data, $existing, 'marketing');
        }
        if ($type === 'purchase_requests') {
            if (! in_array($user->role, OperatingAccess::PROCUREMENT_MANAGERS, true) && (int) $data['requester_id'] !== (int) $this->access->employeeId($user)) {
                $this->fail('تسجّل طلب الشراء باسمك فقط.', 'File purchase requests in your own name.');
            }
            if ((float) $data['estimated_amount'] <= 0) {
                $this->fail('التكلفة التقديرية يجب أن تكون أكبر من صفر.', 'The estimated cost must be above zero.');
            }
        }
        if ($type === 'purchase_orders') {
            if ($data['expected_delivery'] < $data['order_date']) {
                $this->fail('موعد التوريد قبل تاريخ الأمر.', 'Expected delivery is before the order date.');
            }
            if ((float) ($data['tax_amount'] ?? 0) > (float) $data['amount']) {
                $this->fail('الضريبة أكبر من الإجمالي.', 'Tax cannot exceed the total.');
            }
            if (! empty($data['purchase_request_id'])) {
                $request = DB::table('operating_records')->find($data['purchase_request_id']);
                $requestData = $this->catalog->data($request);
                $sameRequest = $existing && (int) ($this->catalog->data($existing)['purchase_request_id'] ?? 0) === (int) $data['purchase_request_id'];
                if ($request->status !== 'approved' && ! ($sameRequest && $request->status === 'ordered')) {
                    $this->fail('أمر الشراء يحتاج طلب شراء معتمدًا لم يصدر له أمر.', 'A purchase order needs an approved request that has not been ordered yet.');
                }
                if (app(OperatingProcurement::class)->currency($requestData) !== app(OperatingProcurement::class)->currency($data)) {
                    $this->fail('عملة الأمر تختلف عن عملة الطلب.', 'The order currency differs from the request currency.');
                }
                if (! empty($requestData['supplier_id']) && (int) $requestData['supplier_id'] !== (int) $data['supplier_id']) {
                    $this->fail('المورد يختلف عن المورد المحدد في الطلب.', 'The supplier differs from the one on the request.');
                }
            }
        }
        if ($type === 'supplier_invoices') {
            $procurement = app(OperatingProcurement::class);
            if ((float) $data['amount'] <= 0 || (float) ($data['tax_amount'] ?? 0) > (float) $data['amount']) {
                $this->fail('راجع الإجمالي والضريبة.', 'Check the total and the tax.');
            }
            if ($data['due_date'] < $data['invoice_date']) {
                $this->fail('تاريخ الاستحقاق قبل تاريخ الفاتورة.', 'The due date is before the invoice date.');
            }
            $number = mb_strtolower(trim((string) $data['invoice_number']));
            if (DB::table('operating_records')->where('type', 'supplier_invoices')->whereNotIn('status', ['void', 'archived'])->where('data->supplier_id', (int) $data['supplier_id'])->when($id, fn ($query) => $query->where('id', '!=', $id))->get()->contains(fn (object $invoice): bool => mb_strtolower(trim((string) ($this->catalog->data($invoice)['invoice_number'] ?? ''))) === $number)) {
                $this->fail('رقم الفاتورة مسجل بالفعل لنفس المورد.', 'This supplier invoice number is already recorded.');
            }
            if (! empty($data['purchase_order_id'])) {
                $order = DB::table('operating_records')->find($data['purchase_order_id']);
                $orderData = $this->catalog->data($order);
                if ((int) $orderData['supplier_id'] !== (int) $data['supplier_id'] || $procurement->currency($orderData) !== $procurement->currency($data)) {
                    $this->fail('الفاتورة يجب أن تطابق مورد أمر الشراء وعملته.', 'The invoice must match the order supplier and currency.');
                }
                if (! in_array($order->status, ['approved', 'received', 'closed'], true)) {
                    $this->fail('الفوترة على أمر شراء معتمد فقط.', 'Only an approved purchase order can be billed.');
                }
                $billed = $procurement->linked('supplier_invoices', 'purchase_order_id', $order->id)->whereIn('status', OperatingProcurement::BILLED_STATES)->where('id', '!=', $id)->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0));
                if ($billed + (float) $data['amount'] > (float) $orderData['amount'] + 0.001) {
                    $this->fail('الفواتير تتجاوز مبلغ أمر الشراء.', 'Invoices exceed the purchase order amount.');
                }
            }
        }
        if ($type === 'assets') {
            if (! empty($data['purchase_date']) && ! empty($data['warranty_end']) && $data['warranty_end'] < $data['purchase_date']) {
                $this->fail('انتهاء الضمان قبل تاريخ الشراء.', 'The warranty ends before the purchase date.');
            }
            if (! empty($data['asset_tag']) && app(OperatingAssets::class)->isTaken('assets', 'asset_tag', $data['asset_tag'], $id)) {
                $this->fail('رقم الأصل مستخدم لأصل آخر.', 'This asset tag is already in use.');
            }
            if (! empty($data['serial_number']) && app(OperatingAssets::class)->isTaken('assets', 'serial_number', $data['serial_number'], $id)) {
                $this->fail('الرقم التسلسلي مسجل لأصل آخر.', 'This serial number is already registered.');
            }
            if (empty($data['asset_tag']) && $existing) {
                $data['asset_tag'] = $this->catalog->data($existing)['asset_tag'] ?? null;
            }
        }
        if ($type === 'inventory_items') {
            if (! empty($data['sku']) && app(OperatingAssets::class)->isTaken('inventory_items', 'sku', $data['sku'], $id)) {
                $this->fail('كود الصنف مستخدم لصنف آخر.', 'This SKU is already in use.');
            }
            if (! $existing) {
                $data['quantity'] = 0;
            }
        }
        if ($type === 'stock_movements') {
            if ((float) $data['quantity'] <= 0 && $data['movement'] !== 'adjust') {
                $this->fail('الكمية يجب أن تكون أكبر من صفر.', 'The quantity must be above zero.');
            }
            if (DB::table('operating_records')->where('id', $data['item_id'])->value('status') !== 'active') {
                $this->fail('الصنف غير نشط.', 'The item is not active.');
            }
        }
        if ($type === 'asset_movements') {
            if (in_array($data['movement'], ['assign', 'transfer'], true)) {
                if (empty($data['to_employee_id'])) {
                    $this->fail('حدّد الموظف المستلم.', 'Choose the receiving employee.');
                }
                if (DB::table('operating_records')->where('id', $data['to_employee_id'])->value('status') !== 'active') {
                    $this->fail('الموظف المستلم غير نشط.', 'The receiving employee is not active.');
                }
            } else {
                $data['to_employee_id'] = null;
            }
        }
        if ($type === 'supplier_payments') {
            $invoice = DB::table('operating_records')->find($data['supplier_invoice_id']);
            if (! in_array($invoice->status, ['approved', 'part_paid'], true)) {
                $this->fail('سجّل الدفعات على فاتورة معتمدة غير مسددة.', 'Record payments against an approved, unpaid invoice.');
            }
            if ((float) $data['amount'] <= 0) {
                $this->fail('المبلغ المدفوع يجب أن يكون أكبر من صفر.', 'The payment must be above zero.');
            }
            $data['currency'] = app(OperatingProcurement::class)->currency($this->catalog->data($invoice));
        }
        if ($type === 'deliverables') {
            $milestone = DB::table('operating_records')->find($data['milestone_id']);
            $milestoneData = $this->catalog->data($milestone);
            $data['project_id'] = $milestoneData['project_id'] ?? null;
            $milestoneDue = ($milestoneData['approved_due'] ?? null) ?: ($milestoneData['original_due'] ?? null);
            if ($milestoneDue && $data['due_date'] > $milestoneDue) {
                $this->fail('موعد المخرج بعد موعد المحطة.', 'The deliverable is due after its milestone.');
            }
            if (! $existing && in_array($milestone->status, ['submitted', 'accepted'], true)) {
                $this->fail('المحطة قُدِّمت للعميل؛ لا تُضاف لها مخرجات جديدة.', 'The milestone was already submitted; add no new deliverables.');
            }
        }
        if ($type === 'tasks' && ! empty($data['sprint_id']) && (int) $data['sprint_id'] !== (int) (($existing ? $this->catalog->data($existing) : [])['sprint_id'] ?? 0) && DB::table('operating_records')->where('id', $data['sprint_id'])->value('status') === 'completed') {
            $this->fail('لا تُضاف مهام إلى سبرنت مكتمل.', 'Tasks cannot join a completed sprint.');
        }
        if ($type === 'projects' && ! empty($data['quote_id'])) {
            if (DB::table('operating_records')->where('id', $data['quote_id'])->value('status') !== 'approved') {
                $this->fail('التحويل إلى مشروع يحتاج عرضًا معتمدًا.', 'Only an approved quote can become a project.');
            }
            if (DB::table('operating_records')->where('type', 'projects')->where('status', '!=', 'archived')->where('data->quote_id', (int) $data['quote_id'])->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('هذا العرض تحوّل إلى مشروع بالفعل.', 'This quote has already been converted to a project.');
            }
        }
        if ($type === 'quotes') {
            if ($data['line_items'] ?? []) {
                $data['totals'] = app(OperatingQuotes::class)->totals($data['line_items'], (float) ($data['discount'] ?? 0));
                $data['price'] = $data['totals']['total'];
            } else {
                if (empty($data['price'])) {
                    $this->fail('أضف بنودًا للعرض أو سجّل سعرًا إجماليًا.', 'Add line items or enter a total price.');
                }
                $data['totals'] = app(OperatingQuotes::class)->totals([['quantity' => 1.0, 'unit_price' => (float) $data['price'], 'discount' => 0.0, 'tax' => 0.0]], (float) ($data['discount'] ?? 0));
            }
        }
        if ($type === 'attendance') {
            if (DB::table('operating_records')->where('type', 'attendance')->where('data->assignee_id', (int) $data['assignee_id'])->where('data->work_date', $data['work_date'])->where('status', '!=', 'archived')->when($id, fn ($query) => $query->where('id', '!=', $id))->exists()) {
                $this->fail('يوجد سجل حضور لهذا الموظف في هذا اليوم.', 'This employee already has an attendance record for that day.');
            }
            if (! empty($data['check_in']) && ! str_starts_with($data['check_in'], $data['work_date'])) {
                $this->fail('وقت الحضور يجب أن يكون في نفس يوم العمل.', 'Check-in must fall on the work date.');
            }
            if (! empty($data['check_out']) && (empty($data['check_in']) || $data['check_out'] <= $data['check_in'])) {
                $this->fail('وقت الانصراف يحتاج وقت حضور سابقًا له.', 'Check-out needs an earlier check-in.');
            }
            $data = app(OperatingAttendance::class)->derive($data, $existing?->status ?? app(OperatingAttendance::class)->initialStatus($data));
        }
        if ($type === 'evaluations' && ! empty($data['ratings'])) {
            $data['calculated_score'] = $this->score($data['ratings']);
        }
        if ($existing) {
            $old = $this->catalog->data($existing);
            if (in_array($existing->status, $entity['immutable'] ?? [], true) && $data !== $old) {
                $this->fail('السجل المعتمد محفوظ. أنشئ إصدارًا جديدًا بدل تعديل الأصل.', 'Approved records are preserved. Create a new version.');
            }
            if (! empty($old['original_due']) && $old['original_due'] !== ($data['original_due'] ?? null)) {
                $this->fail('الموعد الأصلي لا يُغيّر. استخدم الموعد المعتمد مع طلب تغيير.', 'The original deadline is immutable. Use an approved change for the current date.');
            }
            if (($old['approved_due'] ?? null) !== ($data['approved_due'] ?? null) && ! empty($data['approved_due'])) {
                $change = ! empty($data['change_id']) ? DB::table('operating_records')->find($data['change_id']) : null;
                $changeData = $change ? $this->catalog->data($change) : [];
                if (! $change || $change->status !== 'approved' || ($changeData['new_due'] ?? null) !== $data['approved_due'] || ($changeData['project_id'] ?? null) != ($data['project_id'] ?? $existing->id)) {
                    $this->fail('تغيير الموعد يحتاج طلب تغيير معتمد لنفس المشروع والموعد.', 'A deadline change requires an approved change for this project and date.');
                }
            }
        }

        $record = DB::transaction(function () use ($type, $user, $data, $validated, $existing, $entity): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            if ($type === 'projects' && ! empty($data['quote_id']) && DB::table('operating_records')->where('type', 'projects')->where('status', '!=', 'archived')->where('data->quote_id', (int) $data['quote_id'])->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))->lockForUpdate()->exists()) {
                $this->fail('هذا العرض تحوّل إلى مشروع بالفعل.', 'This quote has already been converted to a project.');
            }
            if ($type === 'receipts') {
                $invoice = DB::table('operating_records')->where('id', $data['invoice_id'])->lockForUpdate()->first();
                $received = DB::table('operating_records')->where('type', 'receipts')->where('data->invoice_id', $invoice->id)->where('status', '!=', 'archived')->when($existing, fn ($query) => $query->where('id', '!=', $existing->id))->get()->sum(fn (object $row): float => (float) ($this->catalog->data($row)['amount'] ?? 0));
                if ($received + $data['amount'] > ($this->catalog->data($invoice)['amount'] ?? 0)) {
                    $this->fail('التحصيل يتجاوز مبلغ الفاتورة.', 'Receipts cannot exceed the invoice amount.');
                }
            }
            if ($type === 'supplier_payments') {
                $invoice = DB::table('operating_records')->where('id', $data['supplier_invoice_id'])->lockForUpdate()->first();
                if (app(OperatingProcurement::class)->paidAmount($invoice->id, $existing?->id, ['draft', 'active']) + (float) $data['amount'] > (float) ($this->catalog->data($invoice)['amount'] ?? 0) + 0.001) {
                    $this->fail('الدفعات تتجاوز مبلغ الفاتورة.', 'Payments cannot exceed the invoice amount.');
                }
            }
            if ($existing) {
                $locked = DB::table('operating_records')->where('id', $existing->id)->lockForUpdate()->first();
                if ($locked->version != $validated['version']) {
                    $this->fail('تم تعديل السجل في نافذة أخرى. حدّث الصفحة.', 'This record changed in another window. Refresh first.');
                }
                $before = (array) $locked;
                if (in_array($locked->status, $entity['immutable'] ?? [], true) && ($data !== $this->catalog->data($locked) || $validated['title'] !== $locked->title)) {
                    $this->fail('السجل المعتمد محفوظ. أنشئ إصدارًا جديدًا بدل تعديل الأصل.', 'Approved records are preserved. Create a new version.');
                }
                if ($type === 'deployments' && $locked->status === 'deployed') {
                    $mutable = ['smoke_test', 'handover', 'warranty_start', 'warranty_end'];
                    if (array_diff_assoc(array_diff_key($data, array_flip($mutable)), array_diff_key($this->catalog->data($locked), array_flip($mutable)))) {
                        $this->fail('بعد النشر، أكمل التحقق والتسليم فقط. بيانات قرار الإطلاق محفوظة.', 'After deployment, complete verification and handover only. Launch decision fields are preserved.');
                    }
                }
                if ($type === 'purchase_orders' && in_array($locked->status, ['approved', 'received'], true)) {
                    $committed = fn (array $values): array => array_filter(array_diff_key($values, array_flip(OperatingProcurement::DELIVERY_FIELDS)), fn (mixed $value): bool => $value !== null && $value !== '');
                    if ($committed($data) != $committed($this->catalog->data($locked)) || $validated['title'] !== $locked->title) {
                        $this->fail('بعد الاعتماد، سجّل بيانات التوريد فقط. لأي تغيير آخر ألغِ الأمر وأصدر أمرًا جديدًا.', 'After approval, record delivery details only. Cancel and reissue the order for other changes.');
                    }
                }
                $nextStatus = $locked->status;
                if ($data !== $this->catalog->data($locked)) {
                    $resetStates = ['campaigns' => 'draft', 'briefs' => 'draft', 'social_posts' => 'draft', 'creative_assets' => 'draft', 'videos' => 'draft', 'reviews' => 'pending', 'test_cases' => 'untested', 'releases' => 'draft', 'evaluations' => 'draft', 'deployments' => 'draft', 'acceptances' => 'pending'];
                    if (isset($resetStates[$type]) && in_array($locked->status, ['approved', 'running', 'passed', 'ready', 'finalized', 'scheduled', 'published', 'delivered', 'accepted'], true) && ! ($type === 'evaluations' && ! in_array($user->role, ['owner', 'hr', 'delivery'], true))) {
                        $nextStatus = $resetStates[$type];
                    }
                    if ($type === 'attendance' && in_array($locked->status, ['open', 'closed', 'absent'], true)) {
                        $nextStatus = app(OperatingAttendance::class)->initialStatus($data);
                    }
                    if ($type === 'tasks' && in_array($locked->status, ['code_review', 'qa', 'ready_to_release'], true) && (($data['build_version'] ?? null) !== ($this->catalog->data($locked)['build_version'] ?? null) || ($data['implementation_url'] ?? null) !== ($this->catalog->data($locked)['implementation_url'] ?? null))) {
                        $nextStatus = 'in_progress';
                    }
                }
                DB::table('operating_records')->where('id', $existing->id)->update(['status' => $nextStatus, 'title' => $validated['title'], 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $locked->version + 1, 'updated_at' => now()]);
                $id = $existing->id;
            } else {
                $before = null;
                if ($type === 'quotes') {
                    $data['quote_number'] = app(OperatingQuotes::class)->nextNumber();
                }
                if ($type === 'purchase_orders') {
                    $data['po_number'] = app(OperatingProcurement::class)->nextOrderNumber();
                }
                if ($type === 'assets' && empty($data['asset_tag'])) {
                    $data['asset_tag'] = app(OperatingAssets::class)->nextTag();
                }
                $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => $type, 'title' => $validated['title'], 'status' => $type === 'attendance' ? app(OperatingAttendance::class)->initialStatus($data) : $entity['statuses'][0], 'owner_id' => $user->id, 'created_by' => $user->id, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
            }
            $record = DB::table('operating_records')->find($id);
            DB::table('operating_links')->where('record_id', $id)->delete();
            foreach ($entity['fields'] as $field) {
                if ($field['type'] === 'relation' && ! empty($data[$field['key']])) {
                    DB::table('operating_links')->insert(['record_id' => $id, 'target_id' => $data[$field['key']], 'field' => $field['key']]);
                }
            }
            $this->event($record, $existing ? 'update' : 'create', $validated['reason'], $before, (array) $record, $user->id);

            return $record;
        });
        $this->notifyAssignees($type, $record, $existing ? $this->catalog->data($existing) : [], $user);

        return $record;
    }

    /**
     * Bring an archived sales record back to the stage it was archived from; nothing is ever deleted, so this is the trash's only exit.
     */
    public function restore(int $id, Request $request): object
    {
        $user = $request->user();
        abort_unless(in_array($user->role, OperatingCrm::MANAGERS, true), 403);
        $input = $request->validate(['version' => 'required|integer', 'reason' => 'required|string|max:2000']);
        $record = $this->access->record($user, $id, 'write');
        abort_unless(in_array($record->type, OperatingCrm::RESTORABLE, true) && $record->status === 'archived', 404);

        return DB::transaction(function () use ($record, $input, $user): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $locked = DB::table('operating_records')->where('id', $record->id)->lockForUpdate()->first();
            if ($locked->version != $input['version']) {
                $this->fail('السجل تغير. حدّث الصفحة.', 'Record changed. Refresh first.');
            }
            $data = $this->catalog->data($locked);
            $target = $data['archived_from'] ?? $this->catalog->entity($locked->type)['statuses'][0];
            unset($data['archived_from']);
            DB::table('operating_records')->where('id', $locked->id)->update(['status' => $target, 'data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $locked->version + 1, 'updated_at' => now()]);
            $next = DB::table('operating_records')->find($locked->id);
            $this->event($next, 'restore', $input['reason'], (array) $locked, (array) $next, $user->id);

            return $next;
        });
    }

    /**
     * Tell people when work newly lands on them; nobody is notified about an assignment they made to themselves.
     *
     * @param  array<string, mixed>  $before
     */
    private function notifyAssignees(string $type, object $record, array $before, User $user): void
    {
        $fields = ['tasks' => ['assignee_id', 'reviewer_id', 'tester_id'], 'team_tasks' => ['assignee_id'], 'opportunities' => ['assignee_id'], 'activities' => ['assignee_id']][$type] ?? [];
        $after = $this->catalog->data($record);
        $self = (int) $this->access->employeeId($user);
        foreach ($fields as $field) {
            $employee = (int) ($after[$field] ?? 0);
            if (! $employee || $employee === (int) ($before[$field] ?? 0) || $employee === $self) {
                continue;
            }
            $alert = $type === 'opportunities'
                ? new OperatingAlert('lead_assigned', ['ar' => 'عميل محتمل جديد مسند إليك', 'en' => 'New lead assigned to you'], ['ar' => 'أُسند إليك العميل "'.$record->title.'".', 'en' => 'The lead "'.$record->title.'" was assigned to you.'], '/admin/operations/crm/leads/'.$record->id)
                : new OperatingAlert('work_assigned', ['ar' => 'عمل جديد مسند إليك', 'en' => 'New work assigned to you'], ['ar' => 'أُسند إليك "'.$record->title.'".', 'en' => 'You were assigned "'.$record->title.'".'], '/admin/operations/record/'.$record->id);
            $this->notifier->send([$this->notifier->employeeUser($employee)], $alert);
        }
    }

    public function score(string $ratings): float
    {
        $items = json_decode($ratings, true);
        if (! is_array($items) || ! array_is_list($items) || ! $items) {
            $this->fail('الدرجات قائمة JSON من معيار ووزن ودرجة ودليل.', 'Ratings must be a JSON list with criterion, weight, rating and evidence.');
        }
        $score = 0;
        $weights = 0;
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['criterion']) || empty($item['evidence']) || ! is_numeric($item['weight'] ?? null) || ! is_numeric($item['rating'] ?? null) || $item['rating'] < 1 || $item['rating'] > 5 || $item['weight'] <= 0 || $item['weight'] > 100) {
                $this->fail('كل درجة من 1 إلى 5، مع وزن ودليل ومعيار.', 'Each rating needs a criterion, evidence, weight and score from 1 to 5.');
            }
            $weights += $item['weight'];
            $score += $item['rating'] / 5 * $item['weight'];
        }
        if (abs($weights - 100) > .001) {
            $this->fail('مجموع الأوزان يجب أن يكون 100%.', 'Weights must total 100%.');
        }

        return round($score, 2);
    }

    /**
     * Non-managers may keep or take ownership of their own records; assigning work to someone else is a manager decision.
     *
     * @param  array<string, mixed>  $data
     */
    private function limitAssignment(User $user, array $data, ?object $existing, string $managerRole = 'sales_manager'): void
    {
        if (in_array($user->role, ['owner', $managerRole], true) || empty($data['assignee_id'])) {
            return;
        }
        $previous = $existing ? ($this->catalog->data($existing)['assignee_id'] ?? null) : null;
        if ((int) $data['assignee_id'] !== (int) $this->access->employeeId($user) && (int) $data['assignee_id'] !== (int) $previous) {
            $managerRole === 'marketing'
                ? $this->fail('الإسناد لموظف آخر من صلاحية مدير التسويق.', 'Only the marketing manager can assign this to someone else.')
                : $this->fail('الإسناد لموظف آخر من صلاحية مدير المبيعات.', 'Only a sales manager can assign this to someone else.');
        }
    }

    private function requireFields(array $data, array $fields): void
    {
        $missing = array_filter($fields, fn (string $field): bool => ! isset($data[$field]) || $data[$field] === '' || $data[$field] === false);
        if ($missing) {
            $this->fail('الانتقال يحتاج استكمال: '.implode(', ', $missing), 'Complete the required gate fields: '.implode(', ', $missing));
        }
    }

    private function related(string $type, array $filters): array
    {
        $query = DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived');
        foreach ($filters as $key => $value) {
            $query->where(str_starts_with($key, 'data->') ? $key : $key, $value);
        }

        return $query->get()->all();
    }

    public function transition(int $id, Request $request): object
    {
        $user = $request->user();
        $record = $this->access->record($user, $id);
        abort_unless($this->access->query($user, $record->type, 'write')->where('id', $id)->exists(), 403);
        $entity = $this->catalog->entity($record->type);
        $input = $request->validate(['target' => 'required|in:'.implode(',', $entity['statuses']), 'version' => 'required|integer', 'reason' => 'required|string|max:2000']);

        $next = DB::transaction(function () use ($user, $record, $entity, $input): object {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $record = DB::table('operating_records')->where('id', $record->id)->lockForUpdate()->first();
            if ($record->version != $input['version']) {
                $this->fail('السجل تغير. حدّث الصفحة.', 'Record changed. Refresh first.');
            }
            if ($record->source !== 'findo') {
                $this->fail('حالة Jira للقراءة فقط.', 'Jira status is read-only.');
            }
            $target = $input['target'];
            $approval = in_array($target, ['approved', 'active', 'finalized'], true) && isset($entity['approve_roles']);
            $qaTaskTransition = $record->type === 'tasks' && in_array($target, ['ready_to_release', 'in_progress'], true) && $user->role === 'qa';
            abort_unless($qaTaskTransition || $this->access->can($user, $record->type, $approval ? 'approve' : 'write'), 403);
            if ($target === $record->status) {
                $this->fail('السجل في هذه الحالة بالفعل.', 'The record is already in this state.');
            }
            $data = $this->catalog->data($record);
            $employeeId = $this->access->employeeId($user);
            $this->gate($record, $data, $target, $user, $employeeId);
            $before = (array) $record;
            $changes = ['status' => $target, 'version' => $record->version + 1, 'updated_at' => now()];
            if ($record->type === 'attendance' && $target !== 'archived') {
                $changes['data'] = json_encode(app(OperatingAttendance::class)->derive($data, $target), JSON_UNESCAPED_UNICODE);
            }
            if ($record->type === 'activities' && $target === 'done') {
                $changes['data'] = json_encode(array_merge($data, ['completed_at' => now()->format('Y-m-d\TH:i')]), JSON_UNESCAPED_UNICODE);
            }
            if ($record->type === 'sprints' && $target === 'completed') {
                $changes['data'] = json_encode(array_merge($data, ['completed_summary' => app(OperatingSprints::class)->releaseUnfinished($record, $user, $input['reason'])]), JSON_UNESCAPED_UNICODE);
            }
            if ($record->type === 'purchase_orders' && $target === 'approved') {
                app(OperatingProcurement::class)->markRequestOrdered($record, $user);
            }
            if ($record->type === 'asset_movements' && $target === 'completed') {
                $changes['data'] = json_encode(app(OperatingAssets::class)->applyMovement($record, $user, $input['reason']), JSON_UNESCAPED_UNICODE);
            }
            if ($record->type === 'stock_movements' && $target === 'completed') {
                $changes['data'] = json_encode(app(OperatingAssets::class)->applyStock($record, $user, $input['reason']), JSON_UNESCAPED_UNICODE);
            }
            $derived = $record->type === 'opportunities' ? app(OperatingCrm::class)->onStageChange($data, $record->status, $target) : $data;
            if ($target === 'archived' && in_array($record->type, OperatingCrm::RESTORABLE, true)) {
                $derived['archived_from'] = $record->status;
            }
            if ($derived !== $data && ! isset($changes['data'])) {
                $changes['data'] = json_encode($derived, JSON_UNESCAPED_UNICODE);
            }
            DB::table('operating_records')->where('id', $record->id)->update($changes);
            if ($record->type === 'activities' && $target === 'done' && ! empty($data['opportunity_id']) && in_array($data['kind'] ?? null, OperatingCrm::CONTACT_KINDS, true)) {
                app(OperatingCrm::class)->touchFirstContact((int) $data['opportunity_id'], now()->format('Y-m-d\TH:i'));
            }
            if ($record->type === 'supplier_payments') {
                app(OperatingProcurement::class)->settleInvoice((int) $data['supplier_invoice_id'], $user, $input['reason']);
            }
            if ($record->type === 'supplier_invoices' && $target === 'approved') {
                app(OperatingProcurement::class)->bookProjectCost($record, $user);
            }
            if ($record->type === 'supplier_invoices' && $target === 'void') {
                app(OperatingProcurement::class)->voidProjectCost($record, $user, $input['reason']);
            }
            $next = DB::table('operating_records')->find($record->id);
            $this->event($next, 'transition', $input['reason'], $before, (array) $next, $user->id);

            return $next;
        });
        if ($next->type === 'asset_movements' && $next->status === 'completed') {
            app(OperatingAssets::class)->notifyReceiver($next);
        }
        if ($next->type === 'stock_movements' && $next->status === 'completed') {
            app(OperatingAssets::class)->checkStock(DB::table('operating_records')->find($this->catalog->data($next)['item_id']));
        }

        return $next;
    }

    private function gate(object $record, array $data, string $target, User $user, ?int $employeeId): void
    {
        if ($record->status === 'archived') {
            $this->fail('السجل المؤرشف محفوظ. أنشئ سجلًا جديدًا عند الحاجة.', 'Archived records are preserved. Create a new record when needed.');
        }
        $forwardStates = ['scopes' => ['superseded', 'archived'], 'changes' => ['implemented', 'archived'], 'quotes' => ['archived'], 'contracts' => ['closed', 'archived'], 'budgets' => ['closed', 'archived'], 'receipts' => ['completed', 'archived']];
        if (isset($forwardStates[$record->type]) && in_array($record->status, $this->catalog->entity($record->type)['immutable'] ?? [], true) && ! in_array($target, $forwardStates[$record->type], true)) {
            $this->fail('لا يمكن إعادة فتح النسخة المعتمدة للتعديل.', 'An approved version cannot return to an editable state.');
        }
        if ($target === 'archived') {
            abort_unless(in_array($user->role, ['owner', 'delivery', 'hr', 'marketing', 'sales', 'sales_manager', 'finance', 'seo'], true), 403);
            if ($record->type === 'receipts' && DB::table('operating_records')->where('id', $data['invoice_id'] ?? null)->value('status') === 'paid') {
                $this->fail('صحح حالة الفاتورة أولًا قبل إلغاء تحصيلها.', 'Correct the invoice state before voiding its receipt.');
            }
            if (in_array($record->type, ['asset_movements', 'stock_movements'], true) && $record->status === 'completed') {
                $this->fail('الحركة المكتملة جزء من السجل. صحّحها بحركة معاكسة.', 'A completed movement is part of the record. Correct it with a reverse movement.');
            }
            if ($record->type === 'supplier_invoices' && in_array($record->status, ['approved', 'part_paid', 'paid'], true)) {
                $this->fail('ألغِ الفاتورة المعتمدة (إلغاء) بدل أرشفتها.', 'Void an approved invoice instead of archiving it.');
            }

            return;
        }
        if ($record->type === 'tasks') {
            $flow = ['backlog', 'ready', 'in_progress', 'code_review', 'qa', 'ready_to_release', 'done'];
            $rework = $target === 'in_progress' && in_array($record->status, ['code_review', 'qa'], true) && ($this->related('reviews', ['data->task_id' => $record->id, 'status' => 'failed']) || $this->related('test_cases', ['data->task_id' => $record->id, 'status' => 'failed']) || $this->related('defects', ['data->task_id' => $record->id, 'status' => 'open']));
            if ($user->role === 'qa' && $target === 'in_progress') {
                abort_unless($record->status === 'qa' && $employeeId == ($data['tester_id'] ?? null), 403);
            }
            if (! $rework && array_search($target, $flow, true) !== array_search($record->status, $flow, true) + 1) {
                $this->fail('انتقل عبر مراحل المهمة بالترتيب دون تجاوز بوابات الجودة.', 'Follow task stages in order without skipping quality gates.');
            }
            if ($target === 'ready') {
                $this->requireFields($data, ['project_id', 'requirement_id', 'assignee_id', 'reviewer_id', 'tester_id', 'deliverable', 'acceptance_criteria', 'inputs', 'priority', 'estimate_hours', 'original_due']);
                if ($data['assignee_id'] == $data['reviewer_id']) {
                    $this->fail('المراجع مستقل عن المؤلف.', 'Reviewer must be independent.');
                }
            }
            if ($target === 'in_progress') {
                $project = DB::table('operating_records')->find($data['project_id']);
                $projectData = $this->catalog->data($project);
                if (! in_array($project->status, ['execution', 'acceptance', 'launch'], true) || ! $this->related('scopes', ['data->project_id' => $project->id, 'status' => 'approved']) || DB::table('operating_records')->where('id', $projectData['contract_id'] ?? null)->value('status') !== 'approved') {
                    $this->fail('بدء المهمة يحتاج مشروعًا بدأ التنفيذ بنطاق وعقد معتمدين.', 'Task execution requires an executing project with approved scope and contract.');
                }
                if (DB::table('operating_records')->where('type', 'blockers')->where('data->task_id', $record->id)->whereIn('status', ['open', 'escalated'])->exists()) {
                    $this->fail('المهمة لها عائق مفتوح.', 'This task has an open blocker.');
                }
                if (! empty($data['dependency_id']) && ! in_array(DB::table('operating_records')->where('id', $data['dependency_id'])->value('status'), ['done', 'ready_to_release'], true)) {
                    $this->fail('التبعية لم تُكمل بعد.', 'The dependency is not complete.');
                }
                if ($this->related('tasks', ['data->assignee_id' => $data['assignee_id'], 'status' => 'in_progress'])) {
                    $this->fail('مهمة تنفيذ أساسية واحدة لكل مطور. أكمل العمل الجاري أولًا.', 'One primary active task per developer. Finish current work first.');
                }
            }
            if ($target === 'code_review') {
                $this->requireFields($data, ['implementation_url', 'build_version']);
                if (count($this->related('tasks', ['status' => 'code_review'])) >= config('operating.thresholds.review_wip')) {
                    $this->fail('صف مراجعة الكود بلغ الحد المقترح.', 'The code review queue reached its configured WIP limit.');
                }
            }
            if ($target === 'qa') {
                abort_unless($user->role === 'owner' || $employeeId == ($data['reviewer_id'] ?? null), 403);
                $reviews = $this->related('reviews', ['data->task_id' => $record->id, 'status' => 'passed', 'data->build_version' => $data['build_version']]);
                if (! $reviews) {
                    $this->fail('المهمة تحتاج مراجعة مستقلة ناجحة على نفس الإصدار.', 'A passing independent review is required for this build.');
                }
                if (count($this->related('tasks', ['status' => 'qa'])) >= config('operating.thresholds.qa_wip')) {
                    $this->fail('صف الاختبار بلغ الحد المقترح.', 'QA reached its configured WIP limit.');
                }
            }
            if ($target === 'ready_to_release') {
                abort_unless($user->role === 'owner' || ($user->role === 'qa' && $employeeId == ($data['tester_id'] ?? null)), 403);
                $tests = $this->related('test_cases', ['data->task_id' => $record->id]);
                if (! $tests || array_filter($tests, fn (object $test): bool => $test->status !== 'passed' || ($this->catalog->data($test)['build_version'] ?? null) !== $data['build_version'])) {
                    $this->fail('كل حالات الاختبار المرتبطة تحتاج نجاحًا على الإصدار نفسه.', 'All linked tests must pass on this build.');
                }
                if ($this->related('defects', ['data->task_id' => $record->id, 'data->severity' => 'critical', 'status' => 'open'])) {
                    $this->fail('يوجد عيب حرج مفتوح.', 'An open critical defect blocks release.');
                }
            }
            if ($target === 'done') {
                $this->requireFields($data, ['closure_reason']);
            }
        }
        if ($record->type === 'reviews' && in_array($target, ['passed', 'failed'], true)) {
            $this->requireFields($data, ['reviewer_id', 'task_id', 'evidence', 'checks', 'build_version']);
            $task = $this->catalog->data(DB::table('operating_records')->find($data['task_id']));
            if ($data['reviewer_id'] != ($task['reviewer_id'] ?? null) || ($data['build_version'] ?? null) !== ($task['build_version'] ?? null) || $data['reviewer_id'] == ($task['assignee_id'] ?? null) || $employeeId != $data['reviewer_id'] || ! in_array($user->role, ['developer', 'tech_lead', 'owner'], true) || ($data['result'] ?? null) !== ($target === 'passed' ? 'pass' : 'fail')) {
                $this->fail('القبول الفني للمراجع المستقل المكلّف فقط، مع نتيجة ناجحة.', 'Only the assigned independent reviewer can approve a passing review.');
            }
        }
        if ($record->type === 'test_cases' && in_array($target, ['passed', 'failed'], true)) {
            abort_unless(in_array($user->role, ['owner', 'qa'], true), 403);
            $this->requireFields($data, ['environment', 'build_version', 'actual', 'evidence']);
            if (($data['result'] ?? null) !== ($target === 'passed' ? 'pass' : 'fail')) {
                $this->fail('حالة الاختبار يجب أن تطابق نتيجته.', 'Test state must match the recorded result.');
            }
        }
        if ($record->type === 'defects' && $target === 'verified') {
            abort_unless(in_array($user->role, ['owner', 'qa'], true), 403);
            $this->requireFields($data, ['fix_evidence', 'retest_evidence']);
        }
        if ($record->type === 'changes') {
            if ($target === 'approved') {
                $this->requireFields($data, ['classification', 'effort', 'cost', 'impact', 'options', 'client_approval', 'effective_date', 'start_conditions']);
            }
            if ($target === 'implemented' && $record->status !== 'approved') {
                $this->fail('لا تنفيذ قبل اكتمال الموافقات.', 'Implementation requires approval first.');
            }
        }
        if ($record->type === 'projects') {
            $stages = ['discovery', 'agreement', 'planning', 'execution', 'acceptance', 'launch', 'closed'];
            if ($target !== 'closed' && array_search($target, $stages, true) !== array_search($record->status, $stages, true) + 1) {
                $this->fail('انتقل عبر مراحل المشروع بالترتيب.', 'Follow project stages in order.');
            }
            if ($target === 'execution') {
                $this->requireFields($data, ['manager_id', 'tech_lead_id', 'deploy_owner_id', 'backup_id', 'start_checklist', 'contract_id']);
                if (! $this->related('scopes', ['data->project_id' => $record->id, 'status' => 'approved']) || DB::table('operating_records')->where('id', $data['contract_id'])->value('status') !== 'approved') {
                    $this->fail('بدء التنفيذ يحتاج نطاقًا وعقدًا معتمدين.', 'Execution requires an approved scope and contract.');
                }
            }
            if ($target === 'closed') {
                if (! in_array($record->status, ['execution', 'acceptance', 'launch'], true)) {
                    $this->fail('الإغلاق يأتي بعد تنفيذ المشروع والتحقق من تسليمه.', 'Close a project after execution and verified handover.');
                }
                $this->requireFields($data, ['client_acceptance', 'closure']);
                if (! $this->related('deployments', ['data->project_id' => $record->id, 'status' => 'verified'])) {
                    $this->fail('الإغلاق يحتاج نشرًا متحققًا وتسليمًا.', 'Closure requires verified deployment and handover.');
                }
            }
        }
        if ($record->type === 'sprints') {
            $stages = ['planned', 'active', 'completed'];
            if (array_search($target, $stages, true) !== array_search($record->status, $stages, true) + 1) {
                $this->fail('انتقل عبر مراحل السبرنت بالترتيب.', 'Follow sprint stages in order.');
            }
            if ($target === 'active') {
                if (! in_array(DB::table('operating_records')->where('id', $data['project_id'])->value('status'), ['execution', 'acceptance', 'launch'], true)) {
                    $this->fail('تفعيل السبرنت يحتاج مشروعًا في مرحلة التنفيذ.', 'Start a sprint once the project is in execution.');
                }
                if (DB::table('operating_records')->where('type', 'sprints')->where('status', 'active')->where('data->project_id', (int) $data['project_id'])->where('id', '!=', $record->id)->exists()) {
                    $this->fail('للمشروع سبرنت جارٍ بالفعل. أكمله أولًا.', 'This project already has an active sprint. Complete it first.');
                }
            }
        }
        if ($record->type === 'quotes' && $target === 'approved') {
            if (! empty($data['scope_id'])) {
                if (DB::table('operating_records')->where('id', $data['scope_id'])->value('status') !== 'approved') {
                    $this->fail('العرض المربوط بنطاق يحتاج نسخة نطاق معتمدة.', 'A quote linked to a scope requires an approved scope version.');
                }
            } elseif (empty($data['line_items'])) {
                $this->fail('العرض بدون نطاق يحتاج بنودًا مفصلة قبل الاعتماد.', 'A quote without a scope needs line items before approval.');
            }
        }
        if ($record->type === 'contracts' && $target === 'approved') {
            if (DB::table('operating_records')->where('id', $data['quote_id'])->value('status') !== 'approved') {
                $this->fail('العقد يحتاج عرضًا معتمدًا.', 'The contract requires an approved quote.');
            }
        }
        if ($record->type === 'invoices' && $target === 'paid') {
            $received = DB::table('operating_records')->where('type', 'receipts')->where('data->invoice_id', $record->id)->whereIn('status', ['active', 'completed'])->get()->sum(fn (object $row): float => (float) ($this->catalog->data($row)['amount'] ?? 0));
            if ($received < ($data['amount'] ?? 0)) {
                $this->fail('سجّل دليل تحصيل كامل المبلغ قبل اعتبار الفاتورة مدفوعة.', 'Record receipts for the full amount before marking the invoice paid.');
            }
        }
        if ($record->type === 'receipts' && in_array($target, ['active', 'completed'], true)) {
            $this->requireFields($data, ['reference', 'evidence', 'received_at', 'amount']);
        }
        if ($record->type === 'support' && ($data['kind'] ?? null) === 'enhancement' && in_array($target, ['in_progress', 'resolved', 'closed'], true)) {
            if (empty($data['change_id']) || DB::table('operating_records')->where('id', $data['change_id'])->value('status') !== 'approved') {
                $this->fail('التحسين الجديد يحتاج طلب تغيير معتمدًا.', 'Enhancements require an approved change request.');
            }
        }
        if ($record->type === 'scopes' && $target === 'approved') {
            $this->requireFields($data, ['client_approval']);
        }
        if (in_array($record->type, ['quotes', 'contracts'], true) && $target === 'approved') {
            $this->requireFields($data, $record->type === 'quotes' ? ['approval_evidence'] : ['client_evidence', 'signed_at']);
        }
        if ($record->type === 'milestones') {
            if (in_array($target, ['in_progress', 'submitted'], true)) {
                $this->requireFields($data, ['assignee_id']);
            }
            if ($target === 'submitted' && app(OperatingMilestones::class)->deliverables($record->id)->where('status', '!=', 'delivered')->isNotEmpty()) {
                $this->fail('سلّم كل مخرجات المحطة قبل تقديمها للعميل.', 'Deliver every deliverable before submitting the milestone.');
            }
            if ($target === 'accepted') {
                if ($record->status !== 'submitted') {
                    $this->fail('اقبل المحطة بعد تقديمها.', 'Accept a milestone after it is submitted.');
                }
                $this->requireFields($data, ['client_acceptance', 'accepted_at']);
            }
        }
        if ($record->type === 'purchase_requests') {
            if ($target === 'ordered') {
                $this->fail('يتحوّل الطلب إلى "تم الطلب" تلقائيًا عند اعتماد أمر الشراء.', 'A request becomes ordered automatically when its purchase order is approved.');
            }
            if (in_array($target, ['approved', 'rejected'], true)) {
                if ($record->status !== 'requested') {
                    $this->fail('القرار يُتخذ على الطلب المقدَّم فقط.', 'Only an open request can be approved or rejected.');
                }
                abort_unless($this->access->can($user, 'purchase_requests', 'approve'), 403);
                if ($user->role !== 'owner' && (int) $record->owner_id === $user->id) {
                    $this->fail('لا يعتمد مقدم الطلب طلبه بنفسه.', 'Requesters cannot decide on their own request.');
                }
            }
        }
        if ($record->type === 'purchase_orders') {
            $stages = ['draft', 'submitted', 'approved', 'received', 'closed'];
            if ($target === 'cancelled') {
                if (! in_array($record->status, ['draft', 'submitted', 'approved'], true)) {
                    $this->fail('لا يُلغى أمر شراء بعد استلامه.', 'A received order cannot be cancelled.');
                }
                if (app(OperatingProcurement::class)->orderCommitment($record)['invoices'] > 0) {
                    $this->fail('للأمر فواتير مسجلة. ألغِ الفواتير أولًا.', 'This order has invoices. Void them first.');
                }
            } elseif (array_search($target, $stages, true) !== array_search($record->status, $stages, true) + 1) {
                $this->fail('انتقل عبر مراحل أمر الشراء بالترتيب.', 'Follow purchase order stages in order.');
            }
            if ($target === 'approved') {
                if ((float) ($data['amount'] ?? 0) <= 0) {
                    $this->fail('مبلغ الأمر يجب أن يكون أكبر من صفر.', 'The order amount must be above zero.');
                }
                if (! empty($data['purchase_request_id'])) {
                    $request = DB::table('operating_records')->find($data['purchase_request_id']);
                    if ($request->status !== 'approved') {
                        $this->fail('طلب الشراء لم يعد معتمدًا أو صدر له أمر آخر.', 'The purchase request is no longer approved or was already ordered.');
                    }
                    if ((float) $data['amount'] > (float) ($this->catalog->data($request)['estimated_amount'] ?? 0) * 1.1 + 0.001) {
                        $this->fail('الأمر يتجاوز تقدير الطلب المعتمد بأكثر من 10%. قدّم طلبًا جديدًا.', 'The order exceeds the approved request estimate by more than 10%. File a new request.');
                    }
                }
            }
            if ($target === 'received') {
                $this->requireFields($data, ['delivered_at']);
            }
        }
        if (in_array($record->type, ['asset_movements', 'stock_movements'], true) && ($target !== 'completed' || $record->status !== 'draft')) {
            $this->fail('الحركة تُعتمد من المسودة مرة واحدة، والمكتملة لا تُعدَّل.', 'A movement is completed once from draft; completed movements do not change.');
        }
        if ($record->type === 'assets') {
            if ($target === 'maintenance' || $record->status === 'maintenance') {
                $this->fail('الدخول للصيانة والعودة منها بحركة صيانة على الأصل.', 'Use a maintenance movement to send an asset to or back from maintenance.');
            }
            if ($record->status === 'retired') {
                $this->fail('الأصل المحال للتقاعد لا يعود للخدمة.', 'A retired asset does not return to service.');
            }
            if ($target === 'retired' && ! empty($data['assignee_id'])) {
                $this->fail('استلم الأصل من الموظف بحركة إرجاع قبل إحالته للتقاعد.', 'Record a return from the employee before retiring the asset.');
            }
        }
        if ($record->type === 'supplier_invoices') {
            if (in_array($target, ['part_paid', 'paid'], true)) {
                $this->fail('حالة الدفع تُحسب تلقائيًا من الدفعات المؤكدة.', 'Payment status is calculated from confirmed payments.');
            }
            if ($target === 'draft') {
                $this->fail('لا تعود الفاتورة المعتمدة إلى مسودة. ألغها وسجّل فاتورة جديدة.', 'An approved invoice cannot return to draft. Void it and record a new one.');
            }
            if ($target === 'approved') {
                $this->requireFields($data, ['evidence']);
            }
            if ($target === 'void' && app(OperatingProcurement::class)->paidAmount($record->id) > 0) {
                $this->fail('للفاتورة دفعات مؤكدة. ألغِ الدفعات أولًا.', 'This invoice has confirmed payments. Void them first.');
            }
        }
        if ($record->type === 'supplier_payments') {
            if ($target !== 'active') {
                $this->fail('الدفعة تُؤكَّد أو تُلغى بالأرشفة فقط.', 'A payment is either confirmed or voided by archiving.');
            }
            $this->requireFields($data, ['reference', 'paid_at']);
            $invoice = DB::table('operating_records')->find($data['supplier_invoice_id']);
            if (! in_array($invoice->status, ['approved', 'part_paid'], true) || app(OperatingProcurement::class)->paidAmount($invoice->id, $record->id) + (float) $data['amount'] > (float) ($this->catalog->data($invoice)['amount'] ?? 0) + 0.001) {
                $this->fail('الدفعة تتجاوز المتبقي على الفاتورة أو الفاتورة غير معتمدة.', 'The payment exceeds what is left on the invoice, or the invoice is not approved.');
            }
        }
        if ($record->type === 'team_tasks') {
            if ($target === 'done' && ! in_array($user->role, ['owner', 'marketing'], true) && (int) $record->owner_id !== $user->id) {
                abort(403);
            }
            if (in_array($target, ['review', 'done'], true) && empty($data['deliverable_url']) && empty($data['notes'])) {
                $this->fail('سجّل رابط التسليم أو ملاحظة التنفيذ قبل المراجعة.', 'Add the delivery link or an execution note before review.');
            }
        }
        if ($record->type === 'deliverables' && $target === 'delivered' && empty($data['delivery_url']) && empty($data['evidence'])) {
            $this->fail('سجّل رابط التسليم أو دليله.', 'Add the delivery link or evidence.');
        }
        if ($record->type === 'acceptances' && $target === 'accepted') {
            $this->requireFields($data, ['evidence', 'accepted_at']);
        }
        if ($record->type === 'releases' && in_array($target, ['ready', 'released'], true)) {
            $this->requireFields($data, ['test_report', 'qa_owner_id']);
            $tests = $this->related('test_cases', ['data->project_id' => $data['project_id'], 'data->build_version' => $data['build_version']]);
            if (! $tests || array_filter($tests, fn (object $test): bool => $test->status !== 'passed')) {
                $this->fail('الإصدار يحتاج اختبارات ناجحة كاملة على الإصدار الصحيح.', 'The release requires passing tests for the correct build.');
            }
            $defects = $this->related('defects', ['data->project_id' => $data['project_id'], 'data->severity' => 'critical']);
            if (array_filter($defects, fn (object $defect): bool => $defect->status !== 'verified')) {
                $this->fail('لا جاهزية مع عيب حرج غير متحقق.', 'An unverified critical defect blocks readiness.');
            }
        }
        if ($record->type === 'deployments' && in_array($target, ['approved', 'deployed', 'verified'], true)) {
            if (! in_array(DB::table('operating_records')->where('id', $data['project_id'])->value('status'), ['execution', 'acceptance', 'launch'], true)) {
                $this->fail('النشر يحتاج مشروعًا دخل التنفيذ بعد اكتمال شروط البدء.', 'Deployment requires a project that passed the execution start gate.');
            }
            $this->requireFields($data, ['deploy_owner_id', 'backup_id', 'backup_evidence', 'rollback', 'monitoring', 'acceptance_id']);
            $acceptanceRecord = DB::table('operating_records')->find($data['acceptance_id']);
            if (($this->catalog->data($acceptanceRecord)['release_id'] ?? null) != $data['release_id']) {
                $this->fail('قبول العميل لا يخص هذا الإصدار.', 'Client acceptance must reference this release.');
            }
            if (DB::table('operating_records')->where('id', $data['release_id'])->value('status') !== 'ready' || DB::table('operating_records')->where('id', $data['acceptance_id'])->value('status') !== 'accepted') {
                $this->fail('النشر يحتاج إصدارًا جاهزًا وقبولًا صريحًا من العميل.', 'Deployment requires a ready release and explicit client acceptance.');
            }
            if ($target === 'deployed' && $record->status !== 'approved') {
                $this->fail('النشر يحتاج قرار إطلاق معتمدًا.', 'Deployment requires an approved launch decision.');
            }
            if ($target === 'verified') {
                $this->requireFields($data, ['smoke_test', 'handover']);
                if ($record->status !== 'deployed') {
                    $this->fail('تحقق بعد النشر الفعلي.', 'Verify after deployment.');
                }
            }
        }
        if ($record->type === 'campaigns') {
            if ($target === 'approved') {
                $this->requireFields($data, ['budget_cap', 'channel']);
            }
            if ($target === 'running') {
                $this->requireFields($data, ['landing_url', 'asset_approval', 'tracking_test', 'sales_ready', 'channel']);
                if (! in_array($record->status, ['approved', 'paused'], true) || ! DB::table('operating_events')->where('record_id', $record->id)->where('after->status', 'approved')->exists() || ($data['spent'] ?? 0) >= $data['budget_cap']) {
                    $this->fail('الحملة تحتاج ميزانية معتمدة وإنفاقًا تحت السقف.', 'The campaign needs approved budget and spend below its cap.');
                }
            }
            if ($target === 'approved' && $user->role !== 'owner') {
                $delegation = $this->access->delegation($user, 'campaigns');
                if (! $delegation || ($this->catalog->data($delegation)['budget_limit'] ?? 0) < $data['budget_cap']) {
                    $this->fail('السقف يتجاوز حدود التفويض.', 'The campaign cap exceeds the written delegation.');
                }
            }
        }
        if (in_array($record->type, ['briefs', 'social_posts', 'creative_assets', 'videos'], true) && in_array($target, ['approved', 'scheduled', 'published', 'delivered'], true)) {
            $this->requireFields($data, ['approval_evidence']);
            if (in_array($target, ['scheduled', 'published', 'delivered'], true) && ! in_array($record->status, ['approved', 'scheduled'], true)) {
                $this->fail('اعتمد النسخة قبل الجدولة أو النشر أو التسليم.', 'Approve this version before scheduling, publishing or delivery.');
            }
            if ($record->type === 'social_posts' && ! empty($data['sensitive'])) {
                $this->requireFields($data, ['client_consent']);
                if ($target === 'approved') {
                    abort_unless($user->role === 'owner', 403);
                }
            }
            if ($record->type === 'social_posts' && $target === 'published') {
                $this->requireFields($data, ['published_url']);
            }
            if ($record->type === 'creative_assets') {
                $this->requireFields($data, ['source_url', 'final_url', 'rights', 'dimensions_checked', 'language_checked']);
            }
            if ($record->type === 'videos') {
                $this->requireFields($data, ['source_url', 'final_url', 'rights', 'audio_checked', 'subtitles_checked', 'export_checked']);
            }
        }
        if ($record->type === 'opportunities') {
            if (in_array($target, ['qualified', 'discovery', 'proposal', 'negotiation', 'won'], true)) {
                $this->requireFields($data, ['need', 'solution']);
            }
            if (in_array($target, ['lost', 'deferred', 'unsuitable'], true)) {
                $this->requireFields($data, ['reason']);
            }
            if (in_array($target, ['proposal', 'negotiation', 'won'], true) && empty($data['client_id'])) {
                $this->fail('حوّل العميل المحتمل إلى عميل قبل العرض أو التفاوض أو الفوز.', 'Convert the lead to a client before proposal, negotiation or win.');
            }
            if ($target === 'won') {
                $this->requireFields($data, ['expected_value']);
            }
        }
        if ($record->type === 'activities' && $target === 'done') {
            $this->requireFields($data, ['outcome']);
        }
        if ($record->type === 'evaluations') {
            if (! in_array($user->role, ['owner', 'hr', 'delivery'], true) && ! in_array($target, ['self_review', 'appealed'], true)) {
                abort(403);
            }
            if ($target === 'finalized') {
                $this->requireFields($data, ['ratings', 'self_review', 'manager_review', 'decision_evidence', 'next_steps']);
                $this->score($data['ratings']);
                $goals = $this->related('goals', ['data->assignee_id' => $data['assignee_id'], 'data->period' => $data['period']]);
                if (count($goals) < 3 || count($goals) > 5 || array_filter($goals, fn (object $goal): bool => empty($this->catalog->data($goal)['announced_at']))) {
                    $this->fail('التقييم النهائي يحتاج 3 إلى 5 أهداف معلنة لنفس الموظف والفترة.', 'Finalization requires 3–5 announced goals for this employee and period.');
                }
            }
        }
        if ($record->type === 'delegations' && $target === 'active') {
            if (! isset(config('operating.entities')[$data['entity_type']]) || $data['action'] !== 'approve' || $data['expires_at'] < $data['starts_at']) {
                $this->fail('راجع نطاق التفويض وتواريخه.', 'Review delegation scope and dates.');
            }
        }
        if ($record->type === 'attendance') {
            abort_unless(in_array($user->role, ['owner', 'hr'], true), 403);
            if ($target === 'excused') {
                $this->requireFields($data, ['note']);
            }
            if ($target === 'closed') {
                $this->requireFields($data, ['check_in', 'check_out']);
            }
            if ($target === 'open') {
                $this->requireFields($data, ['check_in']);
            }
        }
        if ($record->type === 'seo_issues' && $target === 'verified') {
            $this->requireFields($data, ['fix', 'recheck_evidence', 'checked_at']);
        }
    }
}
