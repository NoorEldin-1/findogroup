<?php

namespace App\Http\Controllers;

use App\Services\Cms;
use App\Services\OperatingAccess;
use App\Services\OperatingCatalog;
use App\Services\OperatingCrm;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CrmController extends Controller
{
    public function __construct(private OperatingCrm $crm, private OperatingAccess $access, private OperatingCatalog $catalog) {}

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
