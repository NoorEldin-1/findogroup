<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperatingProcurement
{
    /**
     * Fields a purchase order still accepts after approval: the goods arrive after the commitment is made.
     */
    public const DELIVERY_FIELDS = ['delivered_at', 'delivery_note'];

    /**
     * Invoice states that count against an order or towards payables.
     */
    public const BILLED_STATES = ['draft', 'approved', 'part_paid', 'paid'];

    /**
     * Invoices that still owe money to a supplier.
     */
    public const OPEN_INVOICE_STATES = ['approved', 'part_paid'];

    public const NOTICE_DAYS = 7;

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow, private OperatingAccess $access, private OperatingSales $sales, private OperatingNotifier $notifier) {}

    /**
     * One screen for the procurement cycle. Each section appears only for viewers who can read its records;
     * payables and supplier spend are finance data.
     *
     * @return array{pending_requests: list<array<string, mixed>>, approved_requests: list<array<string, mixed>>, pending_orders: list<array<string, mixed>>, late_deliveries: list<array<string, mixed>>, upcoming_deliveries: list<array<string, mixed>>, payables: list<array<string, mixed>>, due_invoices: list<array<string, mixed>>, top_suppliers: list<array<string, mixed>>, finance: bool, orders: bool, counts: array<string, int>}
     */
    public function dashboard(User $user): array
    {
        $today = today()->toDateString();
        $horizon = today()->addDays(self::NOTICE_DAYS)->toDateString();
        $requests = $this->access->can($user, 'purchase_requests') ? $this->access->query($user, 'purchase_requests')->whereIn('status', ['requested', 'approved'])->orderBy('id')->get() : collect();
        $canOrders = $this->access->can($user, 'purchase_orders');
        $orders = $canOrders ? $this->access->query($user, 'purchase_orders')->whereIn('status', ['submitted', 'approved'])->orderBy('id')->get() : collect();
        $canFinance = $this->access->can($user, 'supplier_invoices');
        $invoices = $canFinance ? $this->access->query($user, 'supplier_invoices')->whereIn('status', [...self::OPEN_INVOICE_STATES, 'paid'])->orderBy('id')->get() : collect();
        $titles = $this->sales->titles($requests->merge($orders)->merge($invoices)->flatMap(fn (object $row): array => array_values(array_filter([($data = $this->catalog->data($row))['requester_id'] ?? null, $data['supplier_id'] ?? null])))->unique()->values()->all());

        $requestRow = fn (object $request): array => ['id' => $request->id, 'title' => $request->title, 'requester' => $titles[($data = $this->catalog->data($request))['requester_id'] ?? 0] ?? null, 'amount' => (float) ($data['estimated_amount'] ?? 0), 'currency' => $this->currency($data), 'needed_by' => $data['needed_by'] ?? null];
        $orderRow = fn (object $order): array => ['id' => $order->id, 'title' => $order->title, 'number' => ($data = $this->catalog->data($order))['po_number'] ?? null, 'supplier' => $titles[$data['supplier_id'] ?? 0] ?? null, 'amount' => (float) ($data['amount'] ?? 0), 'currency' => $this->currency($data), 'expected' => $data['expected_delivery'] ?? null];
        $awaitingDelivery = $orders->where('status', 'approved')->filter(fn (object $order): bool => empty($this->catalog->data($order)['delivered_at']));

        $open = $invoices->whereIn('status', self::OPEN_INVOICE_STATES)->map(function (object $invoice) use ($titles, $today): array {
            $data = $this->catalog->data($invoice);
            $overdueDays = $data['due_date'] < $today ? (int) today()->diffInDays($data['due_date'], true) : 0;

            return ['id' => $invoice->id, 'title' => $invoice->title, 'number' => $data['invoice_number'], 'supplier' => $titles[$data['supplier_id']] ?? null, 'supplier_id' => (int) $data['supplier_id'], 'currency' => $this->currency($data), 'due' => $data['due_date'], 'outstanding' => round((float) $data['amount'] - $this->paidAmount($invoice->id), 2), 'overdue_days' => $overdueDays];
        });
        $payables = $open->groupBy('currency')->map(fn (Collection $rows, string $currency): array => [
            'currency' => $currency,
            'outstanding' => round($rows->sum('outstanding'), 2),
            'current' => round($rows->where('overdue_days', 0)->sum('outstanding'), 2),
            'd1_30' => round($rows->whereBetween('overdue_days', [1, 30])->sum('outstanding'), 2),
            'd31_60' => round($rows->whereBetween('overdue_days', [31, 60])->sum('outstanding'), 2),
            'd61_90' => round($rows->whereBetween('overdue_days', [61, 90])->sum('outstanding'), 2),
            'd90_plus' => round($rows->where('overdue_days', '>', 90)->sum('outstanding'), 2),
            'due_7' => round($rows->where('overdue_days', 0)->where('due', '<=', $horizon)->sum('outstanding'), 2),
        ])->values();
        $year = (string) today()->year;
        $topSuppliers = $invoices->filter(fn (object $invoice): bool => str_starts_with((string) ($this->catalog->data($invoice)['invoice_date'] ?? ''), $year))
            ->groupBy(fn (object $invoice): string => ($data = $this->catalog->data($invoice))['supplier_id'].'|'.$this->currency($data))
            ->map(function (Collection $rows, string $key) use ($titles, $open): array {
                [$supplier, $currency] = explode('|', $key);

                return ['id' => (int) $supplier, 'title' => $titles[$supplier] ?? null, 'currency' => $currency, 'spend' => round($rows->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0)), 2), 'outstanding' => round($open->where('supplier_id', (int) $supplier)->where('currency', $currency)->sum('outstanding'), 2)];
            })->sortByDesc('spend')->take(5)->values();

        $board = [
            'pending_requests' => $requests->where('status', 'requested')->map($requestRow)->values()->all(),
            'approved_requests' => $requests->where('status', 'approved')->map($requestRow)->values()->all(),
            'pending_orders' => $orders->where('status', 'submitted')->map($orderRow)->values()->all(),
            'late_deliveries' => $awaitingDelivery->filter(fn (object $order): bool => ($this->catalog->data($order)['expected_delivery'] ?? '9999') < $today)->map($orderRow)->sortBy('expected')->values()->all(),
            'upcoming_deliveries' => $awaitingDelivery->filter(fn (object $order): bool => ($due = $this->catalog->data($order)['expected_delivery'] ?? '0000') >= $today && $due <= $horizon)->map($orderRow)->sortBy('expected')->values()->all(),
            'payables' => $payables->all(),
            'due_invoices' => $open->filter(fn (array $row): bool => $row['due'] <= $horizon)->sortBy('due')->values()->all(),
            'top_suppliers' => $topSuppliers->all(),
        ];

        return $board + ['finance' => $canFinance, 'orders' => $canOrders, 'counts' => array_map('count', $board)];
    }

    /**
     * Context shown on a procurement record page, limited to what the viewer can read.
     *
     * @return array<string, mixed>|null
     */
    public function panel(User $user, object $record): ?array
    {
        $canOrders = $this->access->can($user, 'purchase_orders');
        $canInvoices = $this->access->can($user, 'supplier_invoices');
        if ($record->type === 'suppliers') {
            $invoices = $canInvoices ? $this->linked('supplier_invoices', 'supplier_id', $record->id)->whereIn('status', [...self::OPEN_INVOICE_STATES, 'paid']) : collect();
            $year = (string) today()->year;
            $totals = $invoices->groupBy(fn (object $invoice): string => $this->currency($this->catalog->data($invoice)))->map(fn (Collection $rows, string $currency): array => [
                'currency' => $currency,
                'spend' => round($rows->filter(fn (object $invoice): bool => str_starts_with((string) ($this->catalog->data($invoice)['invoice_date'] ?? ''), $year))->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0)), 2),
                'outstanding' => round($rows->whereIn('status', self::OPEN_INVOICE_STATES)->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0) - $this->paidAmount($invoice->id)), 2),
            ])->values()->all();

            return ['type' => 'suppliers', 'finance' => $canInvoices, 'totals' => $totals, 'orders' => $canOrders ? $this->linked('purchase_orders', 'supplier_id', $record->id)->sortByDesc('id')->take(5)->values()->all() : [], 'invoices' => $invoices->sortByDesc('id')->take(5)->values()->all(), 'assets' => $this->access->can($user, 'assets') ? $this->access->query($user, 'assets')->where('status', '!=', 'archived')->where('data->supplier_id', $record->id)->orderBy('title')->limit(10)->get()->all() : []];
        }
        if ($record->type === 'purchase_requests') {
            return ['type' => 'purchase_requests', 'orders' => $canOrders ? $this->linked('purchase_orders', 'purchase_request_id', $record->id)->all() : []];
        }
        if ($record->type === 'purchase_orders') {
            $data = $this->catalog->data($record);
            $expected = $data['expected_delivery'] ?? null;
            $delivery = ! empty($data['delivered_at']) ? 'delivered' : ($record->status === 'approved' && $expected && $expected < today()->toDateString() ? 'late' : 'waiting');

            return ['type' => 'purchase_orders', 'currency' => $this->currency($data), 'commitment' => $this->orderCommitment($record), 'delivery' => $delivery, 'invoices' => $canInvoices ? $this->linked('supplier_invoices', 'purchase_order_id', $record->id)->all() : [], 'canBill' => $this->access->canCreate($user, 'supplier_invoices') && in_array($record->status, ['approved', 'received', 'closed'], true)];
        }
        if ($record->type === 'supplier_invoices') {
            $data = $this->catalog->data($record);
            $paid = $this->paidAmount($record->id);

            return ['type' => 'supplier_invoices', 'currency' => $this->currency($data), 'amount' => (float) ($data['amount'] ?? 0), 'paid' => $paid, 'outstanding' => round((float) ($data['amount'] ?? 0) - $paid, 2), 'payments' => $this->linked('supplier_payments', 'supplier_invoice_id', $record->id)->all(), 'canPay' => $this->access->canCreate($user, 'supplier_payments') && in_array($record->status, self::OPEN_INVOICE_STATES, true)];
        }

        return null;
    }

    /**
     * Daily: one alert per late delivery date, per invoice entering the due window, and per invoice passing its due date.
     * Markers store the announced date, so a changed date alerts again.
     *
     * @return array{late_deliveries: int, invoices_due: int, invoices_overdue: int}
     */
    public function sendAlerts(CarbonImmutable $now): array
    {
        $sent = ['late_deliveries' => 0, 'invoices_due' => 0, 'invoices_overdue' => 0];
        $today = $now->toDateString();
        $horizon = $now->addDays(self::NOTICE_DAYS)->toDateString();
        $managers = $this->notifier->roles(OperatingAccess::PROCUREMENT_MANAGERS)->all();
        foreach (DB::table('operating_records')->where('type', 'purchase_orders')->where('status', 'approved')->where('data->expected_delivery', '<', $today)->get() as $order) {
            $data = $this->catalog->data($order);
            if (! empty($data['delivered_at']) || ($data['delivery_alerted_for'] ?? null) === $data['expected_delivery']) {
                continue;
            }
            $number = $data['po_number'] ?? $order->title;
            $this->notifier->send([...$managers, $order->owner_id ? User::query()->where('active', true)->find($order->owner_id) : null], new OperatingAlert(
                'delivery_late',
                ['ar' => 'توريد متأخر', 'en' => 'Late delivery'],
                ['ar' => 'أمر الشراء '.$number.' كان موعد توريده '.$data['expected_delivery'].' ولم يُستلم بعد.', 'en' => 'Purchase order '.$number.' was due for delivery on '.$data['expected_delivery'].' and is not received yet.'],
                '/admin/operations/record/'.$order->id,
            ));
            $this->workflow->mark($order, 'delivery_alerted_for', $data['expected_delivery'], 'Late delivery alert sent');
            $sent['late_deliveries']++;
        }
        foreach (DB::table('operating_records')->where('type', 'supplier_invoices')->whereIn('status', self::OPEN_INVOICE_STATES)->where('data->due_date', '<=', $horizon)->get() as $invoice) {
            $data = $this->catalog->data($invoice);
            $overdue = $data['due_date'] < $today;
            $marker = $overdue ? 'overdue_alerted_for' : 'due_alerted_for';
            if (($data[$marker] ?? null) === $data['due_date']) {
                continue;
            }
            $label = $data['invoice_number'].' ('.$invoice->title.')';
            $this->notifier->send($managers, new OperatingAlert(
                $overdue ? 'supplier_invoice_overdue' : 'supplier_invoice_due',
                $overdue ? ['ar' => 'فاتورة مورد متأخرة السداد', 'en' => 'Overdue supplier invoice'] : ['ar' => 'فاتورة مورد مستحقة قريبًا', 'en' => 'Supplier invoice due soon'],
                $overdue ? ['ar' => 'فاتورة المورد '.$label.' تجاوزت تاريخ استحقاقها '.$data['due_date'].'.', 'en' => 'Supplier invoice '.$label.' passed its due date '.$data['due_date'].'.'] : ['ar' => 'فاتورة المورد '.$label.' تستحق في '.$data['due_date'].'.', 'en' => 'Supplier invoice '.$label.' is due on '.$data['due_date'].'.'],
                '/admin/operations/record/'.$invoice->id,
                $overdue,
            ));
            $this->workflow->mark($invoice, $marker, $data['due_date'], $overdue ? 'Overdue supplier invoice alert sent' : 'Supplier invoice due alert sent');
            $sent[$overdue ? 'invoices_overdue' : 'invoices_due']++;
        }

        return $sent;
    }

    public function nextOrderNumber(): string
    {
        $prefix = 'PO-'.now()->year.'-';
        $last = DB::table('operating_records')->where('type', 'purchase_orders')->where('data->po_number', 'like', $prefix.'%')->get()
            ->map(fn (object $order): int => (int) substr((string) ($this->catalog->data($order)['po_number'] ?? ''), strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function currency(array $data): string
    {
        return strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD';
    }

    /**
     * @return Collection<int, object>
     */
    public function linked(string $type, string $field, int $targetId): Collection
    {
        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', $field)->where('target_id', $targetId)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * How much of an order is billed and paid. Voided invoices release their share of the order.
     *
     * @return array{amount: float, billed: float, paid: float, remaining: float, invoices: int}
     */
    public function orderCommitment(object $order): array
    {
        $amount = (float) ($this->catalog->data($order)['amount'] ?? 0);
        $invoices = $this->linked('supplier_invoices', 'purchase_order_id', $order->id)->whereIn('status', self::BILLED_STATES);
        $billed = (float) $invoices->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0));
        $paid = (float) $invoices->sum(fn (object $invoice): float => $this->paidAmount($invoice->id));

        return ['amount' => $amount, 'billed' => round($billed, 2), 'paid' => round($paid, 2), 'remaining' => round($amount - $billed, 2), 'invoices' => $invoices->count()];
    }

    /**
     * @param  list<string>  $statuses
     */
    public function paidAmount(int $invoiceId, ?int $exceptPaymentId = null, array $statuses = ['active']): float
    {
        return round((float) $this->linked('supplier_payments', 'supplier_invoice_id', $invoiceId)
            ->whereIn('status', $statuses)
            ->when($exceptPaymentId, fn (Collection $payments) => $payments->where('id', '!=', $exceptPaymentId))
            ->sum(fn (object $payment): float => (float) ($this->catalog->data($payment)['amount'] ?? 0)), 2);
    }

    /**
     * Runs inside a payment transition: the invoice state always mirrors its confirmed payments.
     */
    public function settleInvoice(int $invoiceId, User $user, string $reason): void
    {
        $invoice = DB::table('operating_records')->where('id', $invoiceId)->lockForUpdate()->first();
        if (! $invoice || ! in_array($invoice->status, ['approved', 'part_paid', 'paid'], true)) {
            return;
        }
        $paid = $this->paidAmount($invoice->id);
        $amount = (float) ($this->catalog->data($invoice)['amount'] ?? 0);
        $status = $paid >= $amount - 0.005 ? 'paid' : ($paid > 0 ? 'part_paid' : 'approved');
        if ($status === $invoice->status) {
            return;
        }
        DB::table('operating_records')->where('id', $invoice->id)->update(['status' => $status, 'version' => $invoice->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($invoice->id), 'transition', 'Payment status recalculated: '.$reason, (array) $invoice, ['status' => $status, 'paid' => $paid], $user->id);
    }

    /**
     * Runs inside the invoice approval transaction: a project invoice becomes one actual cost on that project, net of tax.
     */
    public function bookProjectCost(object $invoice, User $user): ?int
    {
        $data = $this->catalog->data($invoice);
        if (empty($data['project_id']) || DB::table('operating_records')->where('type', 'costs')->where('status', '!=', 'archived')->where('data->supplier_invoice_id', $invoice->id)->exists()) {
            return null;
        }
        $supplier = DB::table('operating_records')->find($data['supplier_id']);
        $cost = [
            'project_id' => (int) $data['project_id'],
            'category' => in_array($this->catalog->data($supplier)['category'] ?? null, ['freelancer', 'services'], true) ? 'contractor' : 'tools',
            'actual_cost' => round((float) $data['amount'] - (float) ($data['tax_amount'] ?? 0), 2),
            'remaining_cost' => 0,
            'cost_date' => $data['invoice_date'],
            'supplier_invoice_id' => $invoice->id,
            'evidence' => 'Supplier invoice '.$data['invoice_number'].' ('.$this->currency($data).')',
        ];
        $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'costs', 'title' => $supplier->title.' · '.$data['invoice_number'], 'status' => 'active', 'owner_id' => $user->id, 'created_by' => $user->id, 'data' => json_encode($cost, JSON_UNESCAPED_UNICODE), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operating_links')->insert([['record_id' => $id, 'target_id' => $cost['project_id'], 'field' => 'project_id'], ['record_id' => $id, 'target_id' => $invoice->id, 'field' => 'supplier_invoice_id']]);
        $this->workflow->event(DB::table('operating_records')->find($id), 'create', 'Booked from approved supplier invoice '.$data['invoice_number'], null, $cost, $user->id);

        return $id;
    }

    /**
     * Runs inside the void transition: the project no longer carries the cost of a cancelled invoice.
     */
    public function voidProjectCost(object $invoice, User $user, string $reason): void
    {
        foreach (DB::table('operating_records')->where('type', 'costs')->where('status', '!=', 'archived')->where('data->supplier_invoice_id', $invoice->id)->lockForUpdate()->get() as $cost) {
            DB::table('operating_records')->where('id', $cost->id)->update(['status' => 'archived', 'version' => $cost->version + 1, 'updated_at' => now()]);
            $this->workflow->event(DB::table('operating_records')->find($cost->id), 'transition', 'Supplier invoice voided: '.$reason, (array) $cost, ['status' => 'archived'], $user->id);
        }
    }

    /**
     * Runs inside the order approval transaction: the request it fulfils is now ordered.
     */
    public function markRequestOrdered(object $order, User $user): void
    {
        $data = $this->catalog->data($order);
        $request = empty($data['purchase_request_id']) ? null : DB::table('operating_records')->where('id', $data['purchase_request_id'])->lockForUpdate()->first();
        if (! $request || $request->status !== 'approved') {
            return;
        }
        DB::table('operating_records')->where('id', $request->id)->update(['status' => 'ordered', 'version' => $request->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($request->id), 'transition', 'Purchase order '.($data['po_number'] ?? $order->id).' approved', (array) $request, ['status' => 'ordered'], $user->id);
    }
}
