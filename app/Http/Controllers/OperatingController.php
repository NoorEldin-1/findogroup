<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\JiraSync;
use App\Services\OperatingAccess;
use App\Services\OperatingAssets;
use App\Services\OperatingAttendance;
use App\Services\OperatingCatalog;
use App\Services\OperatingMarketing;
use App\Services\OperatingMilestones;
use App\Services\OperatingProcurement;
use App\Services\OperatingQuotes;
use App\Services\OperatingReports;
use App\Services\OperatingSprints;
use App\Services\OperatingWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;

class OperatingController extends Controller
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow) {}

    private function view(string $page, array $data = []): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access]);
    }

    public function overview(Request $request): Response
    {
        $groups = [];
        $alerts = [];
        $counts = [];
        foreach ($this->catalog->entities() as $type => $entity) {
            if (! $this->access->can($request->user(), $type)) {
                continue;
            }
            $query = $this->access->query($request->user(), $type);
            $counts[$type] = (clone $query)->where('status', '!=', 'archived')->count();
            $groups[$entity['group']][$type] = $counts[$type];
            foreach ((clone $query)->when(in_array($type, ['attendance', 'activities'], true), fn ($scoped) => $scoped->where('status', $type === 'attendance' ? 'open' : 'planned'), fn ($scoped) => $scoped->where('status', '!=', 'archived'))->get() as $record) {
                foreach ($this->signals($record) as $signal) {
                    $alerts[] = ['record' => $record, 'signal' => $signal];
                }
            }
        }
        $decisionTypes = array_filter(['changes', 'decisions', 'quotes', 'contracts', 'budgets', 'campaigns', 'risks'], fn (string $type): bool => $this->access->can($request->user(), $type));
        $decisions = collect();
        foreach ($decisionTypes as $type) {
            $decisions = $decisions->merge($this->access->query($request->user(), $type)->whereIn('status', ['submitted', 'requested', 'escalated'])->latest('updated_at')->limit(10)->get());
        }
        $groups = array_replace(array_intersect_key(array_fill_keys(array_keys(config('operating.groups')), []), $groups), $groups);

        return $this->view('overview', ['groups' => $groups, 'counts' => $counts, 'alerts' => $alerts, 'decisions' => $decisions->sortByDesc('updated_at')->take(20), 'attendanceToday' => app(OperatingAttendance::class)->today($request->user())]);
    }

    public function listing(string $type, Request $request): Response
    {
        $entity = $this->catalog->entity($type);
        $query = $this->access->query($request->user(), $type);
        if ($request->filled('q')) {
            $query->where('title', 'like', '%'.$request->string('q').'%');
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('project')) {
            $query->where('data->project_id', $request->integer('project'));
        }
        $items = $query->latest('updated_at')->paginate(25)->withQueryString();
        if (($request->expectsJson() || $request->is('admin/api/*')) || $request->input('format') === 'json') {
            return response()->json(['data' => $items->map(fn (object $record): array => $this->serialize($record, $request)), 'page' => $items->currentPage(), 'total' => $items->total()]);
        }

        return $this->view('listing', ['type' => $type, 'entity' => $entity, 'items' => $items]);
    }

    private function serialize(object $record, Request $request): array
    {
        return ['id' => $record->id, 'uuid' => $record->uuid, 'type' => $record->type, 'title' => $record->title, 'status' => $record->status, 'version' => $record->version, 'source' => $record->source, 'synced_at' => $record->synced_at, 'updated_at' => $record->updated_at, 'data' => $this->access->visibleData($request->user(), $record)];
    }

    public function create(string $type, Request $request): Response
    {
        abort_unless($this->access->canCreate($request->user(), $type), 403);
        $initial = [];
        if ($request->filled('from')) {
            $source = $this->access->record($request->user(), $request->integer('from'));
            abort_unless($source->type === $type && $source->source === 'findo', 404);
            $initial = $this->access->visibleData($request->user(), $source);
            if ($type === 'scopes') {
                $initial['scope_version'] = DB::table('operating_records')->where('type', 'scopes')->where('data->project_id', $initial['project_id'])->get()->max(fn (object $scope): int => (int) ($this->catalog->data($scope)['scope_version'] ?? 0)) + 1;
                $initial['client_approval'] = null;
            }
            foreach (['approval_evidence', 'client_evidence', 'signed_at'] as $approval) {
                if (array_key_exists($approval, $initial)) {
                    $initial[$approval] = null;
                }
            }
        }

        $prefillable = collect($this->catalog->entity($type)['fields'])->whereIn('type', ['relation', 'select', 'datetime-local', 'date'])->keyBy('key');
        foreach ((array) $request->input('prefill', []) as $key => $value) {
            if (($prefillable[$key]['type'] ?? null) === 'relation' && is_numeric($value)) {
                $target = $this->access->record($request->user(), (int) $value);
                abort_unless($target->type === $prefillable[$key]['entity'], 404);
                $initial[$key] = (int) $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'select' && is_string($value) && isset($prefillable[$key]['options'][$value])) {
                $initial[$key] = $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'datetime-local' && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}$/', $value)) {
                $initial[$key] = $value;
            }
            if (($prefillable[$key]['type'] ?? null) === 'date' && is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                $initial[$key] = $value;
            }
        }

        $initialTitle = null;
        if ($request->filled('quote')) {
            $draft = app(OperatingQuotes::class)->draftFrom($request->user(), $request->integer('quote'), $type);
            $initial = $draft['data'];
            $initialTitle = $draft['title'];
        }

        return $this->form($type, $request, null, $initial, $initialTitle);
    }

    private function form(string $type, Request $request, ?object $record, array $initial = [], ?string $initialTitle = null): Response
    {
        $fields = $this->access->fields($request->user(), $type, $record);
        $choices = [];
        foreach ($fields as $field) {
            if ($field['type'] === 'relation') {
                $choices[$field['key']] = $this->access->can($request->user(), $field['entity']) ? $this->access->query($request->user(), $field['entity'])->where('status', '!=', 'archived')->orderBy('title')->select('id', 'title', 'status')->get() : collect();
                if ($field['key'] === 'assignee_id' && $request->user()->role === 'sales' && in_array($type, OperatingAccess::SALES_SCOPED, true)) {
                    $choices[$field['key']] = $choices[$field['key']]->whereIn('id', array_filter([$this->access->employeeId($request->user()), $record ? ($this->catalog->data($record)['assignee_id'] ?? null) : null]))->values();
                }
            }
            if ($field['type'] === 'user') {
                $choices[$field['key']] = $request->user()->role === 'owner' ? DB::table('users')->where('active', true)->select('id', 'name as title')->get() : collect();
            }
        }
        $events = $record ? DB::table('operating_events')->leftJoin('users', 'users.id', '=', 'operating_events.user_id')->where('record_id', $record->id)->select('operating_events.*', 'users.name')->latest('operating_events.id')->limit(30)->get() : collect();
        $links = $record ? DB::table('operating_links')->join('operating_records as r', 'r.id', '=', 'operating_links.record_id')->where('target_id', $record->id)->select('r.*')->get()->filter(fn (object $linked): bool => $this->access->can($request->user(), $linked->type) && $this->access->query($request->user(), $linked->type)->where('id', $linked->id)->exists()) : collect();
        $comments = $record ? DB::table('operating_comments')->join('users', 'users.id', '=', 'user_id')->where('record_id', $record->id)->select('operating_comments.*', 'users.name')->orderBy('operating_comments.id')->get() : collect();
        $attachments = $record ? DB::table('operating_attachments')->where('record_id', $record->id)->get() : collect();
        if ($record && $this->access->restrictedContext($request->user(), $record)) {
            $events = $comments = $attachments = collect();
        }

        return $this->view('form', ['type' => $type, 'entity' => $this->catalog->entity($type), 'record' => $record, 'data' => $record ? $this->access->visibleData($request->user(), $record) : $initial, 'fields' => $fields, 'choices' => $choices, 'events' => $events, 'links' => $links, 'comments' => $comments, 'attachments' => $attachments, 'projectReport' => $record && $type === 'projects' ? app(OperatingReports::class)->project($record, $request->user()) : null, 'signals' => $record ? $this->signals($record) : [], 'initialTitle' => $initialTitle, 'sprintProgress' => $record && $type === 'sprints' ? app(OperatingSprints::class)->progress($record) : null, 'milestonePanel' => $record && $type === 'milestones' ? ['progress' => app(OperatingMilestones::class)->progress($record), 'deliverables' => $this->access->can($request->user(), 'deliverables') ? $this->access->query($request->user(), 'deliverables')->where('status', '!=', 'archived')->where('data->milestone_id', $record->id)->orderBy('id')->get() : collect()] : null, 'campaignReport' => $record && $type === 'campaigns' ? app(OperatingMarketing::class)->campaign($request->user(), $record) : null, 'procurementPanel' => $record && in_array($type, ['suppliers', 'purchase_requests', 'purchase_orders', 'supplier_invoices'], true) ? app(OperatingProcurement::class)->panel($request->user(), $record) : null, 'assetPanel' => $record && in_array($type, ['assets', 'inventory_items'], true) ? app(OperatingAssets::class)->panel($request->user(), $record) : null]);
    }

    public function show(int $id, Request $request): Response
    {
        $record = $this->access->record($request->user(), $id);
        if (($request->expectsJson() || $request->is('admin/api/*'))) {
            return response()->json($this->serialize($record, $request));
        }

        return $this->form($record->type, $request, $record);
    }

    public function store(string $type, Request $request): Response
    {
        $record = $this->workflow->save($type, $request);

        return ($request->expectsJson() || $request->is('admin/api/*')) ? response()->json($this->serialize($record, $request), 201) : redirect('/admin/operations/record/'.$record->id)->with('success', __('تم حفظ السجل.'));
    }

    public function update(int $id, Request $request): Response
    {
        $record = $this->access->record($request->user(), $id, 'write');
        $record = $this->workflow->save($record->type, $request, $id);

        return ($request->expectsJson() || $request->is('admin/api/*')) ? response()->json($this->serialize($record, $request)) : back()->with('success', __('تم حفظ السجل.'));
    }

    public function transition(int $id, Request $request): Response
    {
        $record = $this->workflow->transition($id, $request);

        return ($request->expectsJson() || $request->is('admin/api/*')) ? response()->json($this->serialize($record, $request)) : back()->with('success', __('تم تحديث الحالة وتسجيل القرار.'));
    }

    public function comment(int $id, Request $request): Response
    {
        $record = $this->access->record($request->user(), $id);
        abort_if($this->access->restrictedContext($request->user(), $record), 403);
        $input = $request->validate(['body' => 'required|string|max:10000']);
        DB::table('operating_comments')->insert(['record_id' => $id, 'user_id' => $request->user()->id, 'body' => $input['body'], 'created_at' => now()]);
        $this->workflow->event($record, 'comment', 'Discussion entry added', null, null);

        return back()->with('success', __('تمت إضافة الرد.'));
    }

    public function attach(int $id, Request $request): Response
    {
        $record = $this->access->record($request->user(), $id, 'write');
        $request->validate(['file' => 'required|file|mimes:pdf,png,jpg,jpeg,webp,txt,csv,zip,docx,xlsx,pptx|max:51200']);
        $file = $request->file('file');
        $path = $file->store('operating/'.$record->uuid, 'local');
        DB::table('operating_attachments')->insert(['record_id' => $id, 'user_id' => $request->user()->id, 'name' => mb_substr(basename($file->getClientOriginalName()), 0, 250), 'path' => $path, 'mime' => $file->getMimeType(), 'bytes' => $file->getSize(), 'created_at' => now()]);
        $this->workflow->event($record, 'attachment', 'Private evidence attached', null, null);

        return back()->with('success', __('تم حفظ المرفق الخاص.'));
    }

    public function download(int $id, Request $request): Response
    {
        $attachment = DB::table('operating_attachments')->find($id);
        abort_unless($attachment, 404);
        $record = $this->access->record($request->user(), $attachment->record_id);
        abort_if($this->access->restrictedContext($request->user(), $record), 403);

        return Storage::disk('local')->download($attachment->path, $attachment->name, ['X-Content-Type-Options' => 'nosniff']);
    }

    public function settings(): Response
    {
        abort_unless(auth()->user()->role === 'owner', 403);
        $saved = DB::table('operating_settings')->where('key', 'rules')->first();

        $attendanceSaved = DB::table('operating_settings')->where('key', 'attendance')->first();
        $attendancePending = $attendanceSaved ? json_decode($attendanceSaved->data, true) : null;

        return $this->view('settings', ['saved' => $saved, 'rules' => $saved ? json_decode($saved->data, true) : ['thresholds' => config('operating.thresholds'), 'scorecards' => config('operating.scorecards')], 'attendance' => app(OperatingAttendance::class)->rules(), 'attendanceVersion' => (int) ($attendanceSaved?->version ?? 0), 'attendancePending' => ($attendancePending['effective_date'] ?? '') > today()->toDateString() ? $attendancePending : null]);
    }

    public function saveSettings(Request $request): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $validation = ['version' => 'required|integer', 'effective_date' => 'required|date', 'reason' => 'required|string|max:2000'];
        foreach (config('operating.thresholds') as $key => $value) {
            $validation['thresholds.'.$key] = 'required|numeric|min:1|max:100';
        }
        foreach (config('operating.scorecards') as $role => $weights) {
            foreach ($weights as $key => $weight) {
                $validation['scorecards.'.$role.'.'.$key] = 'required|numeric|min:1|max:100';
            }
        }
        $input = $request->validate($validation);
        foreach ($input['scorecards'] as $weights) {
            if (abs(array_sum($weights) - 100) > .001) {
                $this->workflow->fail('مجموع أوزان كل بطاقة يجب أن يكون 100%.', 'Each scorecard must total 100%.');
            }
        }
        DB::transaction(function () use ($input): void {
            $old = DB::table('operating_settings')->where('key', 'rules')->lockForUpdate()->first();
            if (($old?->version ?? 0) != $input['version']) {
                $this->workflow->fail('الإعدادات تغيرت. حدّث الصفحة.', 'Settings changed. Refresh first.');
            }
            $data = ['thresholds' => $input['thresholds'], 'scorecards' => $input['scorecards'], 'effective_date' => $input['effective_date']];
            DB::table('operating_settings')->updateOrInsert(['key' => 'rules'], ['data' => json_encode($data), 'version' => ($old?->version ?? 0) + 1, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
            $this->workflow->event(null, 'configure_rules', $input['reason'], $old ? json_decode($old->data, true) : null, $data);
        });

        return back()->with('success', __('تم اعتماد إعدادات التشغيل وتاريخ سريانها.'));
    }

    public function saveAttendanceSettings(Request $request): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $input = $request->validate(['version' => 'required|integer', 'work_start' => 'required|date_format:H:i', 'work_end' => 'required|date_format:H:i|after:work_start', 'work_days' => 'required|array|min:1', 'work_days.*' => 'integer|between:0,6', 'grace_minutes' => 'required|integer|min:0|max:120', 'bonus_min' => 'required|numeric|min:0|max:100000', 'bonus_max' => 'required|numeric|gte:bonus_min|max:100000', 'bonus_eligibility_percent' => 'required|numeric|min:1|max:100', 'absence_alert_days' => 'required|integer|min:1|max:10', 'effective_date' => 'required|date', 'reason' => 'required|string|max:2000']);
        DB::transaction(function () use ($input): void {
            $old = DB::table('operating_settings')->where('key', 'attendance')->lockForUpdate()->first();
            if (($old?->version ?? 0) != $input['version']) {
                $this->workflow->fail('الإعدادات تغيرت. حدّث الصفحة.', 'Settings changed. Refresh first.');
            }
            $data = ['work_start' => $input['work_start'], 'work_end' => $input['work_end'], 'work_days' => array_values(array_unique(array_map('intval', $input['work_days']))), 'grace_minutes' => (int) $input['grace_minutes'], 'bonus_min' => (float) $input['bonus_min'], 'bonus_max' => (float) $input['bonus_max'], 'bonus_eligibility_percent' => (float) $input['bonus_eligibility_percent'], 'absence_alert_days' => (int) $input['absence_alert_days'], 'effective_date' => $input['effective_date']];
            DB::table('operating_settings')->updateOrInsert(['key' => 'attendance'], ['data' => json_encode($data), 'version' => ($old?->version ?? 0) + 1, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
            $this->workflow->event(null, 'configure_attendance', $input['reason'], $old ? json_decode($old->data, true) : null, $data);
        });

        return back()->with('success', __('تم اعتماد قواعد الحضور والبونص.'));
    }

    public function seoAudit(Request $request): Response
    {
        abort_unless(in_array($request->user()->cmsRole(), ['owner', 'seo'], true), 403);
        Artisan::call('operating:seo-audit', ['--sync' => true]);

        return back()->with('success', __('تم فحص اللغتين وتسجيل الأخطاء التقنية الحالية. التحسينات التحريرية في مركز SEO.'));
    }

    public function permissions(): Response
    {
        abort_unless(auth()->user()->role === 'owner', 403);

        return $this->view('permissions');
    }

    public function reports(Request $request): Response
    {
        $metrics = [];
        if ($this->access->can($request->user(), 'tasks')) {
            $tasks = $this->access->query($request->user(), 'tasks')->where('source', 'findo')->where('status', '!=', 'archived')->get();
            $metrics['المهام المحلية / Local tasks'] = $tasks->count();
            $metrics['صف المراجعة / Review queue'] = $tasks->where('status', 'code_review')->count();
            $metrics['صف الاختبار / QA queue'] = $tasks->where('status', 'qa')->count();
            $metrics['مهام بمعايير قبول / Tasks with acceptance criteria'] = $tasks->filter(fn (object $task): bool => ! empty($this->catalog->data($task)['acceptance_criteria']))->count().' / '.$tasks->count();
        }
        if ($this->access->can($request->user(), 'opportunities')) {
            $opportunities = $this->access->query($request->user(), 'opportunities')->whereNotIn('status', ['won', 'lost', 'unsuitable', 'archived'])->get();
            $metrics['فرص دون متابعة / Leads missing follow-up'] = $opportunities->filter(fn (object $opportunity): bool => empty($this->catalog->data($opportunity)['next_action']) || empty($this->catalog->data($opportunity)['follow_up']))->count().' / '.$opportunities->count();
        }
        $financial = [];
        if ($this->access->can($request->user(), 'costs')) {
            foreach ($this->access->query($request->user(), 'projects')->where('status', '!=', 'archived')->get() as $project) {
                $costs = $this->access->query($request->user(), 'costs')->where('data->project_id', $project->id)->get()->map(fn (object $cost): array => $this->catalog->data($cost));
                $budgets = $this->access->query($request->user(), 'budgets')->where('status', 'approved')->where('data->project_id', $project->id)->first();
                $budget = $budgets ? $this->catalog->data($budgets) : [];
                $actual = $costs->sum('actual_cost');
                $remaining = $costs->sum('remaining_cost');
                $financial[] = ['project' => $project, 'actual' => $actual, 'remaining' => $remaining, 'forecast' => $actual + $remaining, 'budget' => $budget['amount'] ?? null, 'margin' => ! empty($budget['contract_value']) ? round(($budget['contract_value'] - $actual - $remaining) / $budget['contract_value'] * 100, 2) : null];
            }
        }

        return $this->view('reports', ['metrics' => $metrics, 'financial' => $financial]);
    }

    public function integrations(): Response
    {
        abort_unless(in_array(auth()->user()->role, ['owner', 'tech_lead'], true), 403);
        $jira = DB::table('operating_integrations')->where('key', 'jira')->first(['status', 'settings', 'last_success_at', 'last_error']);

        return $this->view('integrations', ['jira' => $jira, 'settings' => $jira ? json_decode($jira->settings, true) : [], 'runs' => DB::table('operating_sync_runs')->select('id', 'integration', 'status', 'attempts', 'records', 'error', 'updated_at')->latest('id')->paginate(20)]);
    }

    public function configureJira(Request $request, JiraSync $sync): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $input = $request->validate(['base_url' => 'required|url:https|max:500', 'email' => 'required|email', 'jql' => 'required|string|max:2000', 'token' => 'nullable|string|max:2000']);
        $sync->configure($input, $input['token'] ?? null);

        return back()->with('success', __('تم حفظ إعدادات الربط.'));
    }

    public function syncJira(Request $request, JiraSync $sync): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $run = $sync->sync();

        return back()->with($run->status === 'succeeded' ? 'success' : 'integration_error', $run->status === 'succeeded' ? __('تمت المزامنة.') : $run->error);
    }

    public function importJira(Request $request, JiraSync $sync): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $request->validate(['file' => 'required|file|mimes:json,txt|max:20000']);
        $payload = json_decode(file_get_contents($request->file('file')->getRealPath()), true);
        if (! is_array($payload['issues'] ?? null)) {
            $this->workflow->fail('الملف يحتاج قائمة issues من تصدير Jira JSON.', 'The file needs an issues list from a Jira JSON export.');
        }
        $run = $sync->import($payload['issues']);

        return back()->with($run->status === 'succeeded' ? 'success' : 'integration_error', $run->status === 'succeeded' ? __('تم استيراد النسخة للقراءة فقط.') : $run->error);
    }

    public function retrySync(int $id, Request $request, JiraSync $sync): Response
    {
        abort_unless($request->user()->role === 'owner', 403);
        $run = DB::table('operating_sync_runs')->find($id);
        abort_unless($run && $run->status === 'failed', 422);
        $result = $run->integration === 'jira-import' ? $sync->import(json_decode($run->payload, true)['issues'] ?? [], $id) : $sync->sync();

        return back()->with($result->status === 'succeeded' ? 'success' : 'integration_error', $result->status === 'succeeded' ? __('تمت إعادة المحاولة.') : $result->error);
    }

    private function signals(object $record): array
    {
        $data = $this->catalog->data($record);
        $signals = [];
        if (in_array($record->status, ['closed', 'done', 'completed', 'archived', 'resolved', 'paid', 'verified', 'void', 'cancelled', 'rejected', 'received', 'ordered'], true)) {
            return [];
        }
        $due = $data['approved_due'] ?? $data['original_due'] ?? $data['due_date'] ?? $data['follow_up'] ?? $data['review_date'] ?? null;
        if ($due && $due < today()->toDateString()) {
            $signals[] = __('الموعد يحتاج مراجعة؛ الأصل محفوظ.');
        }
        if ($record->type === 'tasks' && ($data['estimate_hours'] ?? 0) > 0) {
            $actual = DB::table('operating_records')->where('type', 'time_entries')->where('data->task_id', $record->id)->get()->sum(fn (object $entry): float => ($entryData = $this->catalog->data($entry))['category'] === 'waiting' ? 0 : (float) ($entryData['hours'] ?? 0));
            if ($actual / $data['estimate_hours'] * 100 >= config('operating.thresholds.effort_warning_percent')) {
                $signals[] = __('استهلاك الجهد بلغ حد التنبيه؛ حدّث المتبقي.');
            }
            if ($actual + ($data['remaining_hours'] ?? 0) > $data['estimate_hours'] * (1 + config('operating.thresholds.overrun_warning_percent') / 100)) {
                $signals[] = __('توقع تجاوز التقدير يحتاج مراجعة مبكرة.');
            }
        }
        if ($record->type === 'campaigns' && ($data['spent'] ?? 0) >= ($data['budget_cap'] ?? PHP_INT_MAX)) {
            $signals[] = __('بلغت الحملة سقف الإنفاق. راجع التشغيل الخارجي.');
        }
        if ($record->type === 'blockers' && in_array($record->status, ['open', 'escalated'], true)) {
            $signals[] = __('عائق مفتوح يحتاج صاحب حل وموعد متابعة.');
        }
        if ($record->type === 'attendance' && $record->status === 'open' && ($data['work_date'] ?? '') < today()->toDateString()) {
            $signals[] = __('حضور بدون انصراف مسجل. صحّح السجل.');
        }
        if ($record->type === 'activities' && $record->status === 'planned' && ($data['due_at'] ?? '9999') < now()->format('Y-m-d\TH:i')) {
            $signals[] = __('متابعة تجاوزت موعدها. أنجزها أو أعد جدولتها.');
        }
        if ($record->type === 'purchase_orders' && $record->status === 'approved' && empty($data['delivered_at']) && ($data['expected_delivery'] ?? '9999') < today()->toDateString()) {
            $signals[] = __('التوريد تأخر عن موعده المتوقع. تابع المورد أو سجّل الاستلام.');
        }
        if ($record->type === 'inventory_items' && $record->status === 'active' && (float) ($data['quantity'] ?? 0) <= (float) ($data['min_quantity'] ?? 0)) {
            $signals[] = __('المخزون عند حد إعادة الطلب أو أقل. اطلب كمية جديدة.');
        }
        if ($record->type === 'assets' && ! in_array($record->status, ['retired', 'completed', 'archived'], true) && ! empty($data['warranty_end']) && $data['warranty_end'] >= today()->toDateString() && $data['warranty_end'] <= today()->addDays(30)->toDateString()) {
            $signals[] = __('ضمان الأصل ينتهي خلال 30 يومًا.');
        }
        if ($record->type === 'suppliers' && $record->status === 'active' && ($data['agreement_end'] ?? '9999') < today()->toDateString()) {
            $signals[] = __('اتفاقية المورد منتهية. جدّدها أو حدّث بياناتها.');
        }

        return $signals;
    }
}
