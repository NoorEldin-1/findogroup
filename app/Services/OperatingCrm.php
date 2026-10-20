<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\OperatingAlert;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;

class OperatingCrm
{
    /**
     * Roles that run the sales team: they see the whole team, assign, dismiss SLA alerts and restore archived records.
     */
    public const MANAGERS = ['owner', 'sales_manager'];

    /**
     * Types whose archived records appear in the trash and can be restored to the stage they were archived from.
     */
    public const RESTORABLE = ['opportunities', 'activities', 'clients', 'contacts'];

    /**
     * Progress stages in order; reaching a later stage counts as having passed every earlier one.
     */
    public const FUNNEL = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation', 'won'];

    /**
     * Activity kinds that count as actually reaching the lead (notes and internal tasks do not).
     */
    public const CONTACT_KINDS = ['call', 'meeting', 'follow_up', 'email', 'whatsapp'];

    /**
     * Record types a sales manager may review in the audit log.
     */
    public const AUDITED_TYPES = ['opportunities', 'activities', 'clients', 'contacts', 'quotes', 'contracts', 'campaigns'];

    /**
     * Arabic keys for __(); the English text lives in lang/en.json.
     */
    public const SLA_LABELS = ['pending' => 'ضمن المهلة', 'met' => 'في الموعد', 'late' => 'متأخر', 'breached' => 'متجاوز'];

    public const DEFAULTS = ['sla_enabled' => true, 'sla_hours' => 24, 'round_robin_enabled' => false, 'round_robin_pool' => []];

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow, private OperatingNotifier $notifier) {}

    public function isManager(User $user): bool
    {
        return in_array($user->role, self::MANAGERS, true);
    }

    /**
     * @return array{sla_enabled: bool, sla_hours: int, round_robin_enabled: bool, round_robin_pool: list<int>}
     */
    public function settings(): array
    {
        $saved = DB::table('operating_settings')->where('key', 'crm')->value('data');
        $settings = array_replace(self::DEFAULTS, $saved ? json_decode($saved, true) : []);

        return ['sla_enabled' => (bool) $settings['sla_enabled'], 'sla_hours' => (int) $settings['sla_hours'], 'round_robin_enabled' => (bool) $settings['round_robin_enabled'], 'round_robin_pool' => array_values(array_map('intval', (array) $settings['round_robin_pool']))];
    }

    public function settingsVersion(): int
    {
        return (int) (DB::table('operating_settings')->where('key', 'crm')->value('version') ?? 0);
    }

    /**
     * @param  array{version: int, sla_enabled?: bool|string|null, sla_hours: int, round_robin_enabled?: bool|string|null, round_robin_pool?: list<int|string>|null, reason: string}  $input
     */
    public function saveSettings(User $user, array $input): void
    {
        abort_unless($this->isManager($user), 403);
        $pool = array_values(array_unique(array_map('intval', $input['round_robin_pool'] ?? [])));
        if ($pool && DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->whereIn('id', $pool)->count() !== count($pool)) {
            $this->workflow->fail('قائمة التوزيع تقبل الموظفين النشطين فقط.', 'The rotation only accepts active employees.');
        }
        $data = ['sla_enabled' => (bool) ($input['sla_enabled'] ?? false), 'sla_hours' => (int) $input['sla_hours'], 'round_robin_enabled' => (bool) ($input['round_robin_enabled'] ?? false), 'round_robin_pool' => $pool];
        DB::transaction(function () use ($input, $data, $user): void {
            $old = DB::table('operating_settings')->where('key', 'crm')->lockForUpdate()->first();
            if ((int) ($old?->version ?? 0) !== (int) $input['version']) {
                $this->workflow->fail('تم تعديل الإعدادات في نافذة أخرى. حدّث الصفحة.', 'Settings changed in another window. Refresh first.');
            }
            DB::table('operating_settings')->updateOrInsert(['key' => 'crm'], ['data' => json_encode($data), 'version' => ($old?->version ?? 0) + 1, 'created_at' => $old?->created_at ?? now(), 'updated_at' => now()]);
            $this->workflow->event(null, 'configure_crm', $input['reason'], $old ? json_decode($old->data, true) : null, $data, $user->id);
        });
    }

    /**
     * Next active employee in the rotation; the cursor lives in its own settings row so it never bumps the settings version.
     */
    public function nextAssignee(): ?int
    {
        $settings = $this->settings();
        if (! $settings['round_robin_enabled'] || ! $settings['round_robin_pool']) {
            return null;
        }
        $active = DB::table('operating_records')->where('type', 'employees')->where('status', 'active')->whereIn('id', $settings['round_robin_pool'])->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $pool = array_values(array_filter($settings['round_robin_pool'], fn (int $id): bool => in_array($id, $active, true)));
        if (! $pool) {
            return null;
        }

        return DB::transaction(function () use ($pool): int {
            $row = DB::table('operating_settings')->where('key', 'crm_round_robin')->lockForUpdate()->first();
            $index = array_search((int) ($row ? (json_decode($row->data, true)['last'] ?? 0) : 0), $pool, true);
            $next = $pool[$index === false ? 0 : ($index + 1) % count($pool)];
            DB::table('operating_settings')->updateOrInsert(['key' => 'crm_round_robin'], ['data' => json_encode(['last' => $next]), 'version' => ($row?->version ?? 0) + 1, 'created_at' => $row?->created_at ?? now(), 'updated_at' => now()]);

            return $next;
        });
    }

    public function slaDueAt(CarbonInterface $from): ?string
    {
        $settings = $this->settings();

        return $settings['sla_enabled'] ? $from->copy()->addHours($settings['sla_hours'])->format('Y-m-d\TH:i') : null;
    }

    /**
     * pending: still inside the deadline; met / late: first contact before / after it; breached: open, never contacted, deadline passed.
     */
    public function slaState(object $lead, ?string $now = null): ?string
    {
        $data = $this->catalog->data($lead);
        $due = $data['sla_due_at'] ?? null;
        if (! $due) {
            return null;
        }
        if (! empty($data['first_contact_at'])) {
            return $data['first_contact_at'] <= $due ? 'met' : 'late';
        }
        if (! in_array($lead->status, OperatingSales::OPEN_STAGES, true)) {
            return null;
        }

        return ($now ?? now()->format('Y-m-d\TH:i')) > $due ? 'breached' : 'pending';
    }

    public function openAlert(object $lead, ?string $now = null): bool
    {
        return $this->slaState($lead, $now) === 'breached' && empty($this->catalog->data($lead)['sla_dismissed_at']);
    }

    public function responseHours(object $lead): ?float
    {
        $first = $this->catalog->data($lead)['first_contact_at'] ?? null;

        return $first ? round(max(0, CarbonImmutable::parse($lead->created_at)->diffInMinutes(CarbonImmutable::parse($first))) / 60, 1) : null;
    }

    /**
     * One alert per breached lead; the marker keeps reruns of the schedule from repeating it.
     */
    public function sendSlaAlerts(CarbonImmutable $now): int
    {
        $current = $now->format('Y-m-d\TH:i');
        $sent = 0;
        foreach (DB::table('operating_records')->where('type', 'opportunities')->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->sla_due_at', '<', $current)->get() as $lead) {
            $data = $this->catalog->data($lead);
            if (! empty($data['first_contact_at']) || ! empty($data['sla_breached_at'])) {
                continue;
            }
            $assignee = (int) ($data['assignee_id'] ?? 0);
            $this->notifier->send([$assignee ? $this->notifier->employeeUser($assignee) : null, $assignee ? $this->notifier->managerUser($assignee) : null, ...$this->notifier->roles(['sales_manager'])], new OperatingAlert(
                'crm_sla',
                ['ar' => 'تجاوز مهلة التواصل', 'en' => 'Lead response SLA breached'],
                ['ar' => 'لم يتم التواصل مع "'.$lead->title.'" قبل '.str_replace('T', ' ', $data['sla_due_at']).'.', 'en' => '"'.$lead->title.'" was not contacted before '.str_replace('T', ' ', $data['sla_due_at']).'.'],
                '/admin/operations/crm/leads/'.$lead->id,
            ));
            $this->workflow->mark($lead, 'sla_breached_at', $current, 'Lead response SLA breached');
            $sent++;
        }

        return $sent;
    }

    public function dismissSla(User $user, int $leadId, string $reason): void
    {
        abort_unless($this->isManager($user), 403);
        $lead = DB::table('operating_records')->where('type', 'opportunities')->find($leadId);
        abort_unless($lead, 404);
        if (! $this->openAlert($lead)) {
            $this->workflow->fail('لا يوجد تنبيه SLA مفتوح لهذا العميل.', 'This lead has no open SLA alert.');
        }
        $this->workflow->mark($lead, 'sla_dismissed_at', now()->format('Y-m-d\TH:i'), 'SLA alert dismissed: '.$reason);
    }

    /**
     * The last nine digits identify a number regardless of country prefix or leading zero.
     */
    public function phoneKey(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return strlen($digits) >= 6 ? substr($digits, -9) : null;
    }

    public function phoneOwner(string $phoneKey, ?int $exceptId = null): ?object
    {
        return DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->where('data->phone_key', $phoneKey)->when($exceptId, fn ($query) => $query->where('id', '!=', $exceptId))->orderBy('id')->first();
    }

    /**
     * Derived lead data written with a stage change: first contact when the lead leaves "new", close date on win or loss.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function onStageChange(array $data, string $from, string $target): array
    {
        $now = now()->format('Y-m-d\TH:i');
        if ($from === 'new' && ! in_array($target, ['unsuitable', 'archived'], true) && empty($data['first_contact_at'])) {
            $data['first_contact_at'] = $now;
        }
        if (in_array($target, ['won', 'lost'], true)) {
            $data['closed_at'] = $now;
        }

        return $data;
    }

    public function touchFirstContact(int $leadId, string $at): void
    {
        $lead = DB::table('operating_records')->where('type', 'opportunities')->find($leadId);
        if ($lead && empty($this->catalog->data($lead)['first_contact_at'])) {
            $this->workflow->mark($lead, 'first_contact_at', $at, 'First contact recorded');
        }
    }

    /**
     * Every status each lead has held: its current one plus every transition target in the audit log.
     *
     * @param  list<int>  $ids
     * @return array<int, list<string>>
     */
    public function stageReach(array $ids): array
    {
        $reach = [];
        foreach (array_chunk($ids, 500) as $chunk) {
            foreach (DB::table('operating_records')->whereIn('id', $chunk)->select('id', 'status')->get() as $record) {
                $reach[$record->id][] = $record->status;
            }
            foreach (DB::table('operating_events')->whereIn('record_id', $chunk)->where('action', 'transition')->select('record_id', 'after')->get() as $event) {
                $status = json_decode($event->after ?? 'null', true)['status'] ?? null;
                if ($status) {
                    $reach[$event->record_id][] = $status;
                }
            }
        }

        return $reach;
    }

    /**
     * @param  list<string>  $statuses
     */
    public function reached(array $statuses, string $stage): bool
    {
        $target = array_search($stage, self::FUNNEL, true);
        foreach ($statuses as $status) {
            $index = array_search($status, self::FUNNEL, true);
            if ($index !== false && $index >= $target) {
                return true;
            }
        }

        return false;
    }
}
