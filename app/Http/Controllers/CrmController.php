<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use App\Services\OperatingCrmReports;
use App\Services\OperatingLeadImport;
use App\Services\OperatingLeadRelations;
use App\Services\OperatingLeads;
use App\Services\OperatingSales;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class CrmController extends Controller
{
    public function __construct(private OperatingCrm $crm, private OperatingAccess $access, private OperatingCatalog $catalog, private OperatingLeads $leads, private OperatingCrmReports $reports, private OperatingLeadImport $import, private OperatingLeadRelations $relations) {}

    public function dashboard(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);
        $tab = $request->validate(['tab' => 'nullable|in:leads,shared,following'])['tab'] ?? 'leads';

        return $this->view('crm-dashboard', $this->reports->dashboard($request->user(), $from, $to, $scope) + ['from' => $from, 'to' => $to, 'scope' => $scope, 'tab' => $tab, 'calendar' => $this->reports->calendar($request->user(), today()->format('Y-m'), $scope)]);
    }

    public function funnel(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);

        return $this->view('crm-funnel', $this->reports->funnel($request->user(), $from, $to, $scope) + ['from' => $from, 'to' => $to, 'scope' => $scope]);
    }

    public function team(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        [$from, $to] = $this->period($request);

        return $this->view('crm-team', $this->reports->team($request->user(), $from, $to) + ['from' => $from, 'to' => $to, 'qualityLabels' => array_map(fn (array $label): string => $this->catalog->label($label), (array) collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_quality')['options'])]);
    }

    public function sla(Request $request): Response
    {
        $this->authorizeCrm($request);
        [$from, $to, $scope] = $this->period($request);
        $assignee = $this->crm->isManager($request->user()) ? ($request->integer('assignee') ?: null) : null;
        $activityFields = collect($this->catalog->entity('activities')['fields'])->keyBy('key');
        $labels = fn (string $key): array => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields[$key]['options']);

        return $this->view('crm-sla', $this->reports->sla($request->user(), $from, $to, $scope, $assignee) + ['from' => $from, 'to' => $to, 'scope' => $scope, 'assignee' => $assignee, 'kindLabels' => $labels('kind'), 'resultLabels' => $labels('result'), 'options' => $this->leadOptions($request)]);
    }

    public function calendar(Request $request): Response
    {
        $this->authorizeCrm($request);
        $input = $request->validate(['month' => 'nullable|date_format:Y-m', 'assignee' => 'nullable|integer', 'scope' => 'nullable|in:team,mine']);
        $manager = $this->crm->isManager($request->user());
        $scope = $manager ? ($input['scope'] ?? 'team') : 'mine';
        $assignee = $manager ? ($input['assignee'] ?? null) : null;

        return $this->view('crm-calendar', ['calendar' => $this->reports->calendar($request->user(), $input['month'] ?? today()->format('Y-m'), $scope, $assignee), 'assignee' => $assignee, 'scope' => $scope, 'options' => $this->leadOptions($request), 'canCreate' => $this->access->canCreate($request->user(), 'activities')]);
    }

    public function import(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);

        return $this->view('crm-import', ['result' => session('import_result'), 'fields' => OperatingLeadImport::FIELDS]);
    }

    public function uploadImport(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $request->validate(['file' => 'required|file|max:5120|extensions:csv,txt,xlsx']);

        return redirect('/admin/operations/crm/import/'.$this->import->stage($request->user(), $request->file('file')));
    }

    public function mapImport(string $token, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $staged = $this->import->load($request->user(), $token);
        $mapping = $request->has('map') ? $this->importMapping($request, count($staged['header'])) : $this->import->guessMapping($staged['header']);
        $options = $this->importOptions($request);

        return $this->view('crm-import-map', ['token' => $token, 'file' => $staged['file'], 'header' => $staged['header'], 'mapping' => $mapping, 'options' => $options, 'preview' => $this->import->preview($request->user(), $token, $mapping, $options), 'fields' => OperatingLeadImport::FIELDS, 'choices' => $this->leadOptions($request)]);
    }

    public function commitImport(string $token, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $staged = $this->import->load($request->user(), $token);
        $result = $this->import->commit($request->user(), $token, $this->importMapping($request, count($staged['header'])), $this->importOptions($request));

        return redirect('/admin/operations/crm/import')->with('import_result', $result);
    }

    public function trash(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $types = array_values(array_filter(OperatingCrm::RESTORABLE, fn (string $type): bool => $this->access->can($request->user(), $type)));
        $type = in_array($request->input('type'), $types, true) ? $request->input('type') : null;
        $page = DB::table('operating_records')->whereIn('type', $type ? [$type] : $types)->where('status', 'archived')->latest('updated_at')->paginate(30)->withQueryString();
        $archivedBy = DB::table('operating_events')->leftJoin('users', 'users.id', '=', 'operating_events.user_id')->whereIn('record_id', collect($page->items())->pluck('id'))->where('action', 'transition')->where('after', 'like', '%"status":"archived"%')->orderBy('operating_events.id')->get(['record_id', 'operating_events.created_at', 'reason', 'users.name'])->keyBy('record_id');
        $counts = DB::table('operating_records')->whereIn('type', $types)->where('status', 'archived')->groupBy('type')->selectRaw('type, count(*) as total')->pluck('total', 'type');

        return $this->view('crm-trash', ['page' => $page, 'archivedBy' => $archivedBy, 'counts' => $counts, 'types' => $types, 'type' => $type]);
    }

    public function settings(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);

        return $this->view('crm-settings', ['settings' => $this->crm->settings(), 'version' => $this->crm->settingsVersion(), 'employees' => $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id')]);
    }

    public function saveSettings(Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $input = $request->validate(['version' => 'required|integer|min:0', 'sla_enabled' => 'nullable|boolean', 'sla_hours' => 'required|integer|min:1|max:720', 'round_robin_enabled' => 'nullable|boolean', 'round_robin_pool' => 'nullable|array|max:100', 'round_robin_pool.*' => 'integer', 'reason' => 'required|string|max:2000']);
        $this->crm->saveSettings($request->user(), $input);

        return back()->with('success', __('تم حفظ إعدادات CRM.'));
    }

    public function dismissSla(int $id, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $this->access->record($request->user(), $id, 'write');
        $input = $request->validate(['reason' => 'required|string|max:2000']);
        $this->crm->dismissSla($request->user(), $id, $input['reason']);

        return back()->with('success', __('تم إخفاء تنبيه SLA.'));
    }

    public function leads(Request $request): Response
    {
        $this->authorizeCrm($request);
        $filters = $this->leadFilters($request);
        $columns = array_values(array_intersect((array) $request->input('columns', OperatingLeads::DEFAULT_COLUMNS), array_keys(OperatingLeads::COLUMNS))) ?: OperatingLeads::DEFAULT_COLUMNS;
        $perPage = in_array($request->integer('per_page'), OperatingLeads::PER_PAGE, true) ? $request->integer('per_page') : 25;
        $page = $this->leads->query($request->user(), $filters)->latest('created_at')->paginate($perPage)->withQueryString();

        return $this->view('crm-leads', ['filters' => $filters, 'columns' => $columns, 'perPage' => $perPage, 'page' => $page, 'rows' => $this->leads->rows($page->items()), 'options' => $this->leadOptions($request)]);
    }

    public function exportLeads(Request $request): Response
    {
        $this->authorizeCrm($request);
        $filters = $this->leadFilters($request);
        $input = $request->validate(['columns' => 'required|array|min:1', 'columns.*' => 'in:'.implode(',', array_keys(OperatingLeads::COLUMNS)), 'mask_phones' => 'nullable|boolean', 'reason' => 'nullable|string|max:2000']);
        $binary = $this->leads->export($request->user(), $filters, array_values($input['columns']), (bool) ($input['mask_phones'] ?? false), $input['reason'] ?? 'Leads export');

        return response($binary, 200, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'Content-Disposition' => 'attachment; filename="leads-'.now()->format('Ymd-His').'.xlsx"']);
    }

    public function lead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id);
        abort_unless($lead->type === 'opportunities', 404);
        $activityFields = collect($this->catalog->entity('activities')['fields'])->keyBy('key');

        return $this->view('crm-lead', $this->leads->profile($request->user(), $lead) + [
            'kinds' => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields['kind']['options']),
            'results' => array_map(fn (array $label): string => $this->catalog->label($label), (array) $activityFields['result']['options']),
            'employees' => $this->crm->isManager($request->user()) ? $this->access->query($request->user(), 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id') : collect(),
            'sharedWith' => $this->relations->sharedWith($lead),
            'shareable' => $this->relations->shareableUsers($request->user(), $lead),
            'following' => $this->relations->isFollowing($request->user(), $lead),
        ]);
    }

    public function shareLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $input = $request->validate(['user_id' => 'required|integer']);
        $this->relations->share($request->user(), $lead, (int) $input['user_id']);

        return back()->with('success', __('تمت مشاركة العميل.'));
    }

    public function unshareLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $input = $request->validate(['user_id' => 'required|integer']);
        $this->relations->unshare($request->user(), $lead, (int) $input['user_id']);

        return back()->with('success', __('تم إلغاء المشاركة.'));
    }

    public function followLead(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id);
        abort_unless($lead->type === 'opportunities', 404);

        return back()->with('success', $this->relations->toggleFollow($request->user(), $lead) ? __('أنت تتابع هذا العميل الآن.') : __('تم إلغاء المتابعة.'));
    }

    public function handover(int $id, Request $request): Response
    {
        $this->authorizeCrm($request, manager: true);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        $input = $request->validate(['assignee_id' => 'required|integer', 'note' => 'required|string|max:2000']);
        abort_unless(DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->where('id', $input['assignee_id'])->exists(), 422);
        $this->leads->handover($request->user(), $lead, (int) $input['assignee_id'], $input['note']);

        return back()->with('success', __('تم تسليم العميل للزميل.'));
    }

    public function logActivity(int $id, Request $request): Response
    {
        $this->authorizeCrm($request);
        $lead = $this->access->record($request->user(), $id, 'write');
        abort_unless($lead->type === 'opportunities', 404);
        abort_unless($this->access->can($request->user(), 'activities', 'write'), 403);
        $kinds = array_keys(collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'kind')['options']);
        $results = array_keys(collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'result')['options']);
        $input = $request->validate(['kind' => 'required|in:'.implode(',', $kinds), 'result' => 'nullable|in:'.implode(',', $results), 'outcome' => 'required|string|max:5000', 'next_step' => 'nullable|string|max:500', 'follow_up_at' => 'nullable|date_format:Y-m-d\TH:i|after:now']);
        app(OperatingSales::class)->logActivity($request->user(), $lead, $input);

        return back()->with('success', __('تم تسجيل النشاط.'));
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function period(Request $request): array
    {
        $input = $request->validate(['from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'scope' => 'nullable|in:team,mine']);

        return [$input['from'] ?? today()->startOfMonth()->format('Y-m-d'), $input['to'] ?? today()->endOfMonth()->format('Y-m-d'), $this->crm->isManager($request->user()) ? ($input['scope'] ?? 'team') : 'mine'];
    }

    /**
     * @return array<string, mixed>
     */
    private function leadFilters(Request $request): array
    {
        return $request->validate(['q' => 'nullable|string|max:200', 'date_field' => 'nullable|in:created,contact,closed', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'stage' => 'nullable|string|max:40', 'quality' => 'nullable|string|max:40', 'fit' => 'nullable|string|max:40', 'source' => 'nullable|string|max:40', 'channel' => 'nullable|string|max:40', 'campaign' => 'nullable|integer', 'assignee' => 'nullable|integer', 'added_by' => 'nullable|integer', 'sla' => 'nullable|in:breached', 'relation' => 'nullable|in:shared,following']);
    }

    /**
     * Choices for the lead filters; the people lists are only offered to managers because a rep already sees just their own leads.
     *
     * @return array<string, array<int|string, string>>
     */
    private function leadOptions(Request $request): array
    {
        $user = $request->user();
        $entity = $this->catalog->entity('opportunities');
        $fields = collect($entity['fields'])->keyBy('key');
        $labels = fn (string $key): array => array_map(fn (array $label): string => $this->catalog->label($label), json_decode(json_encode($fields[$key]['options']), true));

        return [
            'stages' => collect($entity['statuses'])->reject(fn (string $status): bool => $status === 'archived')->mapWithKeys(fn (string $status): array => [$status => $this->catalog->status($status)])->all(),
            'quality' => $labels('lead_quality'), 'fit' => $labels('fit_status'), 'source' => $labels('lead_source'), 'channel' => $labels('lead_channel'),
            'campaigns' => $this->access->can($user, 'campaigns') ? $this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->orderBy('title')->pluck('title', 'id')->all() : [],
            'assignees' => $this->crm->isManager($user) ? $this->access->query($user, 'employees')->where('status', 'active')->orderBy('title')->pluck('title', 'id')->all() : [],
            'users' => $this->crm->isManager($user) ? DB::table('users')->where('active', true)->orderBy('name')->pluck('name', 'id')->all() : [],
        ];
    }

    /**
     * @return array<int, string|null>
     */
    private function importMapping(Request $request, int $columns): array
    {
        $input = $request->validate(['map' => 'required|array', 'map.*' => 'nullable|in:'.implode(',', array_keys(OperatingLeadImport::FIELDS))]);
        $mapping = [];
        for ($index = 0; $index < $columns; $index++) {
            $mapping[$index] = $input['map'][$index] ?? null;
        }
        $chosen = array_values(array_filter($mapping));
        if (count($chosen) !== count(array_unique($chosen))) {
            throw ValidationException::withMessages(['map' => __('لا يمكن ربط نفس الحقل بعمودين.')]);
        }
        if (! array_intersect($chosen, ['title', 'lead_phone', 'lead_company'])) {
            throw ValidationException::withMessages(['map' => __('اربط عمود الاسم أو الهاتف أو الشركة على الأقل.')]);
        }

        return $mapping;
    }

    /**
     * @return array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}
     */
    private function importOptions(Request $request): array
    {
        $sources = array_keys(collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', 'lead_source')['options']);
        $choices = $this->leadOptions($request);
        $input = $request->validate(['source' => 'nullable|in:'.implode(',', $sources), 'campaign_id' => ['nullable', 'integer', Rule::in(array_keys($choices['campaigns']))], 'assignee_id' => ['nullable', 'integer', Rule::in(array_keys($choices['assignees']))], 'skip_sla' => 'nullable|boolean']);

        return ['source' => $input['source'] ?? 'other', 'campaign_id' => isset($input['campaign_id']) ? (int) $input['campaign_id'] : null, 'assignee_id' => isset($input['assignee_id']) ? (int) $input['assignee_id'] : null, 'skip_sla' => $request->has('map') ? (bool) ($input['skip_sla'] ?? false) : true];
    }

    /**
     * Every CRM screen needs read access to leads; manager-only screens also need a sales manager or the owner.
     */
    private function authorizeCrm(Request $request, bool $manager = false): void
    {
        abort_unless($this->access->can($request->user(), 'opportunities'), 403);
        abort_if($manager && ! $this->crm->isManager($request->user()), 403);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function view(string $page, array $data): Response
    {
        return response()->view('admin.operating.'.$page, $data + ['collections' => Cms::COLLECTIONS, 'catalog' => $this->catalog, 'access' => $this->access, 'crm' => $this->crm]);
    }
}
