<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class OperatingSales
{
    public const OPEN_STAGES = ['new', 'contacted', 'meeting', 'qualified', 'discovery', 'proposal', 'negotiation'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow) {}

    /**
     * Save through the generic workflow so validation, gates, links and the audit event stay identical to the UI path.
     *
     * @param  array<string, mixed>  $payload
     */
    public function saveAs(User $user, string $type, array $payload, ?int $id = null): object
    {
        $request = Request::create('/', 'POST', $payload);
        $request->setUserResolver(fn (): User => $user);

        return $this->workflow->save($type, $request, $id);
    }

    public function transitionAs(User $user, int $id, string $target, string $reason): object
    {
        $record = DB::table('operating_records')->find($id);
        $request = Request::create('/', 'POST', ['target' => $target, 'version' => $record->version, 'reason' => $reason]);
        $request->setUserResolver(fn (): User => $user);

        return $this->workflow->transition($id, $request);
    }

    /**
     * A touchpoint logged after the fact: the activity is created and closed in one step, with an optional planned follow-up.
     *
     * @param  array{kind: string, result?: string|null, outcome: string, next_step?: string|null, follow_up_at?: string|null}  $input
     */
    public function logActivity(User $user, object $lead, array $input): object
    {
        $leadData = $this->catalog->data($lead);
        $employee = $this->access->employeeId($user) ?? (int) ($leadData['assignee_id'] ?? 0);
        if (! $employee) {
            $this->workflow->fail('اربط حسابك بملف موظف أو أسند العميل لمسؤول قبل تسجيل النشاط.', 'Link your account to an employee file or assign the lead before logging activity.');
        }
        $kinds = collect($this->catalog->entity('activities')['fields'])->firstWhere('key', 'kind')['options'];
        $label = $this->catalog->label($kinds[$input['kind']]);
        $link = ['opportunity_id' => $lead->id, 'client_id' => $leadData['client_id'] ?? null, 'assignee_id' => $employee];

        return DB::transaction(function () use ($user, $lead, $input, $label, $link): object {
            $activity = $this->saveAs($user, 'activities', ['title' => $label.' — '.$lead->title, 'reason' => 'Logged from lead profile', 'data' => $link + ['kind' => $input['kind'], 'due_at' => now()->format('Y-m-d\TH:i'), 'result' => $input['result'] ?? null, 'outcome' => $input['outcome'], 'next_step' => $input['next_step'] ?? null, 'priority' => 'normal']]);
            $this->transitionAs($user, $activity->id, 'done', 'Logged from lead profile');
            if (! empty($input['follow_up_at'])) {
                $this->saveAs($user, 'activities', ['title' => __('متابعة').' — '.$lead->title, 'reason' => 'Follow-up planned from lead profile', 'data' => $link + ['kind' => 'follow_up', 'due_at' => $input['follow_up_at'], 'agenda' => $input['next_step'] ?? null, 'priority' => 'normal']]);
            }

            return $activity;
        });
    }

    /**
     * @param  array{assignee?: int|string|null, source?: string|null}  $filters
     * @return array{columns: array<string, array{cards: list<array<string, mixed>>, count: int, value: array<string, float>}>, summary: array{open: int, weighted: array<string, float>, won_month: int, won_month_value: array<string, float>, lost_month: int, win_rate: float|null}}
     */
    public function pipeline(User $user, array $filters = []): array
    {
        $records = $this->access->query($user, 'opportunities')->where('status', '!=', 'archived')
            ->when($filters['assignee'] ?? null, fn ($query, $assignee) => $query->where('data->assignee_id', (int) $assignee))
            ->when($filters['source'] ?? null, fn ($query, $source) => $query->where('data->lead_source', $source))
            ->orderBy('updated_at', 'desc')->get();
        $ids = [];
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            $ids[] = $data['client_id'] ?? null;
            $ids[] = $data['assignee_id'] ?? null;
        }
        $titles = $this->titles(array_values(array_unique(array_filter($ids))));
        $closedAt = $this->closedAt($records->whereIn('status', ['won', 'lost'])->pluck('id')->all());
        $monthStart = today()->startOfMonth()->toDateTimeString();
        $columns = array_fill_keys(self::OPEN_STAGES, ['cards' => [], 'count' => 0, 'value' => []]);
        $summary = ['open' => 0, 'weighted' => [], 'won_month' => 0, 'won_month_value' => [], 'lost_month' => 0, 'win_rate' => null];
        $won = $lost = 0;
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            $currency = $this->currency($data);
            $value = (float) ($data['expected_value'] ?? 0);
            if (in_array($record->status, ['won', 'lost'], true)) {
                $record->status === 'won' ? $won++ : $lost++;
                if ((string) ($closedAt[$record->id] ?? '') >= $monthStart) {
                    if ($record->status === 'won') {
                        $summary['won_month']++;
                        $summary['won_month_value'][$currency] = ($summary['won_month_value'][$currency] ?? 0) + $value;
                    } else {
                        $summary['lost_month']++;
                    }
                }

                continue;
            }
            if (! isset($columns[$record->status])) {
                continue;
            }
            $summary['open']++;
            $summary['weighted'][$currency] = round(($summary['weighted'][$currency] ?? 0) + $value * (float) ($data['probability'] ?? 0) / 100, 2);
            $columns[$record->status]['count']++;
            $columns[$record->status]['value'][$currency] = ($columns[$record->status]['value'][$currency] ?? 0) + $value;
            $columns[$record->status]['cards'][] = [
                'id' => $record->id,
                'title' => $record->title,
                'company' => $titles[$data['client_id'] ?? 0] ?? ($data['lead_company'] ?? null),
                'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null,
                'value' => $value,
                'currency' => $currency,
                'probability' => $data['probability'] ?? null,
                'follow_up' => $data['follow_up'] ?? null,
                'overdue' => ! empty($data['follow_up']) && $data['follow_up'] < today()->toDateString(),
            ];
        }
        $summary['win_rate'] = $won + $lost ? round($won / ($won + $lost) * 100, 1) : null;

        return ['columns' => $columns, 'summary' => $summary];
    }

    public function convertToClient(User $user, int $opportunityId, string $reason): object
    {
        $opportunity = $this->access->record($user, $opportunityId, 'write');
        abort_unless($opportunity->type === 'opportunities', 404);
        abort_unless($this->access->can($user, 'clients', 'write'), 403);
        $data = $this->catalog->data($opportunity);
        if (! empty($data['client_id'])) {
            $this->workflow->fail('الفرصة مرتبطة بعميل بالفعل.', 'This lead is already linked to a client.');
        }
        if (empty($data['lead_company']) || empty($data['lead_contact'])) {
            $this->workflow->fail('أكمل اسم الشركة وجهة الاتصال قبل التحويل.', 'Complete the lead company and contact before converting.');
        }

        return DB::transaction(function () use ($user, $opportunity, $data, $reason): object {
            $client = $this->saveAs($user, 'clients', ['title' => $data['lead_company'], 'reason' => $reason, 'data' => ['company' => $data['lead_company'], 'decision_maker' => ($data['decision_maker'] ?? null) ?: $data['lead_contact'], 'contact' => $data['lead_contact'], 'email' => $data['lead_email'] ?? null, 'phone' => $data['lead_phone'] ?? null]]);
            $this->saveAs($user, 'opportunities', ['title' => $opportunity->title, 'version' => $opportunity->version, 'reason' => $reason, 'data' => array_merge($data, ['client_id' => $client->id, 'lead_source' => ($data['lead_source'] ?? null) ?: 'other'])], $opportunity->id);

            return $client;
        });
    }

    /**
     * Everything linked to a client, each section filtered by the viewer's own access; a null section means the viewer cannot read that type.
     *
     * @return array{client: object, data: array<string, mixed>, sections: array<string, Collection<int, object>|null>, outstanding: array<string, float>|null, collected: array<string, float>|null, stats: array{open_leads: int, won_value: array<string, float>, active_projects: int}, timeline: list<array{at: string, title: string, label: string, id: int}>}
     */
    public function clientProfile(User $user, object $client): array
    {
        $linked = function (string $type, string $field, array $targets) use ($user): ?Collection {
            if (! $this->access->can($user, $type)) {
                return null;
            }
            if (! $targets) {
                return collect();
            }
            $ids = DB::table('operating_links')->where('field', $field)->whereIn('target_id', $targets)->pluck('record_id');

            return $this->access->query($user, $type)->whereIn('id', $ids)->where('status', '!=', 'archived')->latest('updated_at')->get();
        };
        $sections = [];
        foreach (['contacts', 'opportunities', 'activities', 'contracts', 'projects', 'invoices'] as $type) {
            $sections[$type] = $linked($type, 'client_id', [$client->id]);
        }
        $sections['quotes'] = $linked('quotes', 'opportunity_id', $sections['opportunities']?->pluck('id')->all() ?? []);
        $sections['receipts'] = $linked('receipts', 'invoice_id', $sections['invoices']?->pluck('id')->all() ?? []);
        $sections['support'] = $linked('support', 'project_id', $sections['projects']?->pluck('id')->all() ?? []);
        $outstanding = $collected = null;
        if ($sections['invoices'] !== null && $sections['receipts'] !== null) {
            $outstanding = $collected = $paid = [];
            foreach ($sections['receipts']->whereIn('status', ['active', 'completed']) as $receipt) {
                $receiptData = $this->catalog->data($receipt);
                $paid[$receiptData['invoice_id']] = ($paid[$receiptData['invoice_id']] ?? 0) + (float) ($receiptData['amount'] ?? 0);
            }
            foreach ($sections['invoices'] as $invoice) {
                $invoiceData = $this->catalog->data($invoice);
                $currency = $this->currency($invoiceData);
                $collected[$currency] = ($collected[$currency] ?? 0) + ($paid[$invoice->id] ?? 0);
                if (in_array($invoice->status, ['issued', 'part_paid', 'overdue'], true)) {
                    $outstanding[$currency] = ($outstanding[$currency] ?? 0) + max(0, (float) ($invoiceData['amount'] ?? 0) - ($paid[$invoice->id] ?? 0));
                }
            }
        }
        $wonValue = [];
        foreach ($sections['opportunities']?->where('status', 'won') ?? [] as $won) {
            $wonData = $this->catalog->data($won);
            $wonValue[$this->currency($wonData)] = ($wonValue[$this->currency($wonData)] ?? 0) + (float) ($wonData['expected_value'] ?? 0);
        }

        return [
            'client' => $client,
            'data' => $this->access->visibleData($user, $client),
            'sections' => $sections,
            'outstanding' => $outstanding,
            'collected' => $collected,
            'stats' => ['open_leads' => $sections['opportunities']?->whereIn('status', self::OPEN_STAGES)->count() ?? 0, 'won_value' => $wonValue, 'active_projects' => $sections['projects']?->whereNotIn('status', ['closed', 'archived'])->count() ?? 0],
            'timeline' => $this->timeline($client, $sections),
        ];
    }

    /**
     * Completed activities plus create/status events of records the viewer can already read; no field snapshots are exposed.
     *
     * @param  array<string, Collection<int, object>|null>  $sections
     * @return list<array{at: string, title: string, label: string, id: int}>
     */
    private function timeline(object $client, array $sections): array
    {
        $timeline = [];
        foreach ($sections['activities'] ?? [] as $activity) {
            $activityData = $this->catalog->data($activity);
            $timeline[] = ['at' => str_replace('T', ' ', $activityData['completed_at'] ?? $activityData['due_at'] ?? (string) $activity->updated_at), 'title' => $activity->title, 'label' => $this->catalog->label($this->catalog->entity('activities')['label']).' · '.$this->catalog->status($activity->status), 'id' => $activity->id];
        }
        $recordIds = collect([$client->id]);
        foreach (['opportunities', 'quotes', 'contracts', 'projects'] as $type) {
            $recordIds = $recordIds->merge($sections[$type]?->pluck('id') ?? []);
        }
        $events = DB::table('operating_events')->join('operating_records as r', 'r.id', '=', 'operating_events.record_id')->whereIn('operating_events.record_id', $recordIds->all())->whereIn('operating_events.action', ['create', 'transition'])->select('operating_events.created_at', 'operating_events.action', 'operating_events.after', 'r.title', 'r.id', 'r.type')->latest('operating_events.id')->limit(40)->get();
        foreach ($events as $event) {
            $status = $event->action === 'transition' ? (json_decode($event->after ?? 'null', true)['status'] ?? null) : null;
            $timeline[] = ['at' => (string) $event->created_at, 'title' => $event->title, 'label' => $this->catalog->label($this->catalog->entity($event->type)['label']).' · '.($status ? __('انتقل إلى').' '.$this->catalog->status($status) : __('تم الإنشاء')), 'id' => $event->id];
        }
        usort($timeline, fn (array $a, array $b): int => strcmp($b['at'], $a['at']));

        return array_slice($timeline, 0, 60);
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int, string>
     */
    public function titles(array $ids): array
    {
        return $ids ? DB::table('operating_records')->whereIn('id', $ids)->pluck('title', 'id')->all() : [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function currency(array $data): string
    {
        return strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD';
    }

    /**
     * @param  list<int>  $ids
     * @return array<int, string>
     */
    private function closedAt(array $ids): array
    {
        if (! $ids) {
            return [];
        }

        return DB::table('operating_events')->whereIn('record_id', $ids)->where('action', 'transition')->groupBy('record_id')->selectRaw('record_id, max(created_at) as closed_at')->pluck('closed_at', 'record_id')->all();
    }
}
