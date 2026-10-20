<?php

namespace App\Services;

use App\Models\User;
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

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow) {}

    public function isManager(User $user): bool
    {
        return in_array($user->role, self::MANAGERS, true);
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
