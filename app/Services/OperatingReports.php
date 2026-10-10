<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperatingReports
{
    public function __construct(private OperatingAccess $access, private OperatingCatalog $catalog) {}

    public function project(object $project, User $user): array
    {
        $data = $this->catalog->data($project);
        $rows = [];
        foreach (['scopes', 'tasks', 'milestones', 'changes', 'test_cases', 'releases', 'acceptances', 'deployments', 'blockers', 'time_entries', 'capacity', 'invoices'] as $type) {
            $rows[$type] = $this->access->can($user, $type) ? $this->access->query($user, $type)->where('data->project_id', $project->id)->where('status', '!=', 'archived')->get() : collect();
        }
        $approvedScope = $rows['scopes']->where('status', 'approved')->count();
        $criticalDefects = $this->access->can($user, 'defects') ? $this->access->query($user, 'defects')->where('data->project_id', $project->id)->where('data->severity', 'critical')->whereNotIn('status', ['verified', 'archived'])->count() : null;
        $date = $data['approved_due'] ?? $data['original_due'] ?? null;
        $employeeIds = array_filter([$data['manager_id'] ?? null, $data['tech_lead_id'] ?? null, $data['deploy_owner_id'] ?? null]);
        $capacityCount = $this->access->can($user, 'capacity') ? $this->access->query($user, 'capacity')->whereIn('data->assignee_id', $employeeIds)->where('status', '!=', 'archived')->count() : 0;
        $health = [
            ['label' => __('النطاق'), 'state' => $approvedScope ? 'green' : 'unknown', 'reason' => $approvedScope ? __('نطاق معتمد محفوظ بإصدار.') : __('لا يوجد نطاق معتمد مسجل.')],
            ['label' => __('الموعد'), 'state' => ! $date ? 'unknown' : ($date < today()->toDateString() ? 'red' : 'green'), 'reason' => __('الأصلي').': '.($data['original_due'] ?? '—').' · '.__('المعتمد').': '.($data['approved_due'] ?? '—')],
            ['label' => __('الجودة'), 'state' => $criticalDefects === null || $rows['test_cases']->isEmpty() ? 'unknown' : ($criticalDefects > 0 || $rows['test_cases']->where('status', 'failed')->count() ? 'red' : ($rows['test_cases']->where('status', 'untested')->count() ? 'yellow' : 'green')), 'reason' => __('ناجح / فاشل / غير مختبر').': '.$rows['test_cases']->where('status', 'passed')->count().' / '.$rows['test_cases']->where('status', 'failed')->count().' / '.$rows['test_cases']->where('status', 'untested')->count()],
            ['label' => __('اعتماد العميل'), 'state' => $rows['acceptances']->isEmpty() ? 'unknown' : ($rows['acceptances']->where('status', 'accepted')->count() ? 'green' : 'yellow'), 'reason' => __('قبول صريح مسجل').': '.$rows['acceptances']->where('status', 'accepted')->count()],
            ['label' => __('السعة'), 'state' => ! $capacityCount ? 'unknown' : ($rows['blockers']->whereIn('status', ['open', 'escalated'])->count() ? 'yellow' : 'green'), 'reason' => __('خطط سعة مسجلة').': '.$capacityCount.' · '.__('عوائق مفتوحة').': '.$rows['blockers']->whereIn('status', ['open', 'escalated'])->count()],
        ];
        if ($this->access->can($user, 'invoices')) {
            $health[] = ['label' => __('الدفع'), 'state' => $rows['invoices']->isEmpty() ? 'unknown' : ($rows['invoices']->whereIn('status', ['overdue'])->count() ? 'red' : 'green'), 'reason' => __('الفواتير المسجلة').': '.$rows['invoices']->count()];
        }
        $hours = ['execution' => 0, 'waiting' => 0];
        foreach ($rows['time_entries'] as $entry) {
            $entryData = $this->catalog->data($entry);
            $hours[($entryData['category'] ?? '') === 'waiting' ? 'waiting' : 'execution'] += (float) ($entryData['hours'] ?? 0);
        }

        $members = $this->access->can($user, 'project_members') ? $this->access->query($user, 'project_members')->where('status', 'active')->whereIn('id', DB::table('operating_links')->where('field', 'project_id')->where('target_id', $project->id)->select('record_id'))->orderBy('title')->get() : collect();

        return ['health' => $health, 'hours' => $hours, 'rows' => $rows, 'updated_at' => $project->updated_at, 'progress' => $this->access->can($user, 'tasks') ? app(OperatingProjects::class)->progress($project) : null, 'members' => $members];
    }
}
