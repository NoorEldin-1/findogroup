<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingSprints
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * Every non-archived task planned in the sprint, regardless of who is viewing (progress is an aggregate).
     *
     * @return Collection<int, object>
     */
    public function tasks(object $sprint): Collection
    {
        return DB::table('operating_records')->where('type', 'tasks')->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', 'sprint_id')->where('target_id', $sprint->id)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * Runs inside the sprint transition transaction: unfinished work goes back to the backlog with an audited reason, done work stays in the sprint history.
     *
     * @return array{done: int, carried: int}
     */
    public function releaseUnfinished(object $sprint, User $user, string $reason): array
    {
        $summary = ['done' => 0, 'carried' => 0];
        foreach ($this->tasks($sprint) as $task) {
            if ($task->status === 'done') {
                $summary['done']++;

                continue;
            }
            $summary['carried']++;
            $data = array_merge($this->catalog->data($task), ['sprint_id' => null, 'carried_from_sprint' => $sprint->id]);
            DB::table('operating_records')->where('id', $task->id)->update(['data' => json_encode($data, JSON_UNESCAPED_UNICODE), 'version' => $task->version + 1, 'updated_at' => now()]);
            DB::table('operating_links')->where('record_id', $task->id)->where('field', 'sprint_id')->delete();
            $this->workflow->event(DB::table('operating_records')->find($task->id), 'update', 'Returned to backlog when sprint "'.$sprint->title.'" completed: '.$reason, ['sprint_id' => $sprint->id], ['sprint_id' => null], $user->id);
        }

        return $summary;
    }
}
