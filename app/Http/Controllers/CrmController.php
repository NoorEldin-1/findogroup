<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use App\Services\OperatingLeads;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class CrmController extends Controller
{
    public function __construct(private OperatingCrm $crm, private OperatingAccess $access, private OperatingCatalog $catalog, private OperatingLeads $leads) {}

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

    /**
     * @return array<string, mixed>
     */
    private function leadFilters(Request $request): array
    {
        return $request->validate(['q' => 'nullable|string|max:200', 'date_field' => 'nullable|in:created,contact,closed', 'from' => 'nullable|date_format:Y-m-d', 'to' => 'nullable|date_format:Y-m-d|after_or_equal:from', 'stage' => 'nullable|string|max:40', 'quality' => 'nullable|string|max:40', 'fit' => 'nullable|string|max:40', 'source' => 'nullable|string|max:40', 'channel' => 'nullable|string|max:40', 'campaign' => 'nullable|integer', 'assignee' => 'nullable|integer', 'added_by' => 'nullable|integer', 'sla' => 'nullable|in:breached']);
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
