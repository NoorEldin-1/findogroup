<?php

namespace App\Console\Commands;

use App\Services\OperatingCatalog;
use App\Services\OperatingWorkflow;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeTaskPriorities extends Command
{
    protected $signature = 'operating:normalize-task-priorities {--dry-run : List the changes without writing them}';

    protected $description = 'Map legacy free-text task priorities to the fixed low/medium/high/critical list';

    /**
     * Known spellings, lower-cased and trimmed. Anything else becomes medium and keeps its original text in priority_note.
     */
    private const MAP = [
        'critical' => 'critical', 'urgent' => 'critical', 'blocker' => 'critical', 'highest' => 'critical', 'p0' => 'critical', 'حرجة' => 'critical', 'حرج' => 'critical', 'عاجل' => 'critical', 'عاجلة' => 'critical',
        'high' => 'high', 'p1' => 'high', 'عالية' => 'high', 'عالي' => 'high', 'مرتفعة' => 'high',
        'medium' => 'medium', 'normal' => 'medium', 'p2' => 'medium', 'متوسطة' => 'medium', 'متوسط' => 'medium', 'عادية' => 'medium',
        'low' => 'low', 'lowest' => 'low', 'minor' => 'low', 'p3' => 'low', 'p4' => 'low', 'منخفضة' => 'low', 'منخفض' => 'low',
    ];

    public function handle(OperatingCatalog $catalog, OperatingWorkflow $workflow): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $count = DB::transaction(function () use ($catalog, $workflow, $dryRun): int {
            if (DB::getDriverName() === 'pgsql') {
                DB::select("SELECT pg_advisory_xact_lock(hashtext('findo-operating-gates'))");
            }
            $count = 0;
            foreach (DB::table('operating_records')->where('type', 'tasks')->where('source', 'findo')->lockForUpdate()->orderBy('id')->get() as $task) {
                $data = $catalog->data($task);
                $value = $data['priority'] ?? null;
                if ($value === null || $value === '' || in_array($value, ['low', 'medium', 'high', 'critical'], true)) {
                    continue;
                }
                $known = self::MAP[mb_strtolower(trim((string) $value))] ?? null;
                $after = array_merge($data, ['priority' => $known ?? 'medium'], $known ? [] : ['priority_note' => (string) $value]);
                $count++;
                $this->line(sprintf('#%d %s: "%s" → %s', $task->id, $task->title, $value, $after['priority']));
                if ($dryRun) {
                    continue;
                }
                DB::table('operating_records')->where('id', $task->id)->update(['data' => json_encode($after, JSON_UNESCAPED_UNICODE), 'version' => $task->version + 1, 'updated_at' => now()]);
                $workflow->event(DB::table('operating_records')->find($task->id), 'update', 'Normalize legacy priority to the fixed list', ['priority' => $value], ['priority' => $after['priority']]);
            }

            return $count;
        });
        $this->info(sprintf('%d tasks normalized%s.', $count, $dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}
