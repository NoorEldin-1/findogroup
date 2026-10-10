<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class OperatingFollowUps
{
    public const BUCKETS = ['overdue', 'today', 'week', 'later', 'leads', 'done'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingNotifier $notifier, private OperatingWorkflow $workflow, private OperatingSales $sales) {}

    /**
     * @return array{overdue: list<array<string, mixed>>, today: list<array<string, mixed>>, week: list<array<string, mixed>>, later: list<array<string, mixed>>, leads: list<array<string, mixed>>, done: list<array<string, mixed>>, counts: array<string, int>}
     */
    public function board(User $user, ?int $assigneeId = null): array
    {
        $now = now()->format('Y-m-d\TH:i');
        $todayEnd = today()->format('Y-m-d').'T23:59';
        $weekEnd = today()->addDays(7)->format('Y-m-d').'T23:59';
        $board = array_fill_keys(self::BUCKETS, []);
        $scoped = fn (string $status) => $this->access->query($user, 'activities')->where('status', $status)->when($assigneeId, fn ($query) => $query->where('data->assignee_id', $assigneeId));
        $planned = $scoped('planned')->get();
        $done = $scoped('done')->where('updated_at', '>=', now()->subDays(30))->latest('updated_at')->limit(50)->get();
        $ids = [];
        foreach ($planned->merge($done) as $row) {
            $data = $this->catalog->data($row);
            $ids[] = $data['assignee_id'] ?? null;
            $ids[] = $data['client_id'] ?? null;
        }
        $titles = $this->sales->titles(array_values(array_unique(array_filter($ids))));
        foreach ($planned->sortBy(fn (object $row): string => $this->catalog->data($row)['due_at'] ?? '') as $row) {
            $due = $this->catalog->data($row)['due_at'] ?? '';
            $bucket = $due < $now ? 'overdue' : ($due <= $todayEnd ? 'today' : ($due <= $weekEnd ? 'week' : 'later'));
            $board[$bucket][] = $this->row($row, $titles);
        }
        foreach ($done as $row) {
            $board['done'][] = $this->row($row, $titles);
        }
        if (! $assigneeId && $this->access->can($user, 'opportunities')) {
            foreach ($this->access->query($user, 'opportunities')->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->follow_up', '<', today()->toDateString())->get() as $lead) {
                $leadData = $this->catalog->data($lead);
                $board['leads'][] = ['id' => $lead->id, 'title' => $lead->title, 'kind' => null, 'due_at' => $leadData['follow_up'] ?? null, 'assignee' => null, 'client' => $leadData['lead_company'] ?? null, 'status' => $lead->status];
            }
        }
        $board['counts'] = array_map('count', $board);

        return $board;
    }

    /**
     * Notify the owner shortly before a follow-up is due, and escalate once when it slips past its time.
     *
     * @return array{due: int, overdue: int}
     */
    public function sendReminders(CarbonImmutable $now): array
    {
        $sent = ['due' => 0, 'overdue' => 0];
        $current = $now->format('Y-m-d\TH:i');
        $soon = $now->addHour()->format('Y-m-d\TH:i');
        $planned = DB::table('operating_records')->where('type', 'activities')->where('status', 'planned')->where('data->due_at', '<=', $soon)->get();
        foreach ($planned as $activity) {
            $data = $this->catalog->data($activity);
            $assignee = (int) ($data['assignee_id'] ?? 0);
            $url = '/admin/operations/record/'.$activity->id;
            if (($data['due_at'] ?? '') < $current) {
                if (! empty($data['overdue_alerted_at'])) {
                    continue;
                }
                $this->notifier->send([$this->notifier->employeeUser($assignee), $this->notifier->managerUser($assignee), ...$this->notifier->roles(['sales_manager'])], new OperatingAlert(
                    'follow_up_overdue',
                    ['ar' => 'متابعة متأخرة', 'en' => 'Overdue follow-up'],
                    ['ar' => 'المتابعة "'.$activity->title.'" تجاوزت موعدها '.str_replace('T', ' ', $data['due_at']).'.', 'en' => 'The follow-up "'.$activity->title.'" passed its due time '.str_replace('T', ' ', $data['due_at']).'.'],
                    $url,
                ));
                $this->mark($activity, 'overdue_alerted_at', $current, 'Overdue follow-up alert sent');
                $sent['overdue']++;

                continue;
            }
            if (! empty($data['reminded_at'])) {
                continue;
            }
            $this->notifier->send([$this->notifier->employeeUser($assignee)], new OperatingAlert(
                'follow_up_due',
                ['ar' => 'تذكير بمتابعة', 'en' => 'Follow-up reminder'],
                ['ar' => 'موعد "'.$activity->title.'" الساعة '.substr($data['due_at'], 11).'.', 'en' => '"'.$activity->title.'" is due at '.substr($data['due_at'], 11).'.'],
                $url,
            ));
            $this->mark($activity, 'reminded_at', $current, 'Follow-up reminder sent');
            $sent['due']++;
        }

        return $sent;
    }

    /**
     * Reminder markers are system bookkeeping: they are audited but do not bump the record version, so they never conflict with a user editing the same follow-up.
     *
     * Only the marker key is written (JSON path update), so a concurrent user edit to the same record is never overwritten.
     */
    private function mark(object $activity, string $key, string $value, string $reason): void
    {
        DB::table('operating_records')->where('id', $activity->id)->update(['data->'.$key => $value]);
        $this->workflow->event($activity, 'reminder', $reason, null, [$key => $value]);
    }

    /**
     * @param  array<int, string>  $titles
     * @return array<string, mixed>
     */
    private function row(object $row, array $titles): array
    {
        $data = $this->catalog->data($row);

        return ['id' => $row->id, 'title' => $row->title, 'kind' => $data['kind'] ?? null, 'due_at' => $data['due_at'] ?? null, 'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null, 'client' => $titles[$data['client_id'] ?? 0] ?? null, 'status' => $row->status];
    }
}
