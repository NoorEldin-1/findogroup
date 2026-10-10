<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingMilestones
{
    /**
     * States in which a passed due date is the team's delay; a submitted milestone is waiting on the client.
     */
    public const OPEN = ['planned', 'in_progress'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * @return Collection<int, object>
     */
    public function deliverables(int $milestoneId): Collection
    {
        return DB::table('operating_records')->where('type', 'deliverables')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'milestone_id')->where('target_id', $milestoneId)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * @return array{percent: ?int, delivered: int, total: int, delayed: int, due: ?string, overdue: bool, due_soon: bool}
     */
    public function progress(object $milestone): array
    {
        $data = $this->catalog->data($milestone);
        $deliverables = $this->deliverables($milestone->id);
        $delivered = $deliverables->where('status', 'delivered')->count();
        $today = today()->toDateString();
        $due = ($data['approved_due'] ?? null) ?: ($data['original_due'] ?? null);
        $open = in_array($milestone->status, self::OPEN, true);

        return [
            'percent' => $milestone->status === 'accepted' ? 100 : ($deliverables->count() ? (int) round($delivered / $deliverables->count() * 100) : null),
            'delivered' => $delivered,
            'total' => $deliverables->count(),
            'delayed' => $deliverables->filter(fn (object $item): bool => $item->status !== 'delivered' && ($this->catalog->data($item)['due_date'] ?? '9999') < $today)->count(),
            'due' => $due,
            'overdue' => $open && $due !== null && $due < $today,
            'due_soon' => $open && $due !== null && $due >= $today && $due <= today()->addDays(7)->toDateString(),
        ];
    }
}
