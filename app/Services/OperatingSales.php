<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OperatingSales
{
    public const OPEN_STAGES = ['new', 'contacted', 'qualified', 'discovery', 'proposal', 'negotiation'];

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
            $this->saveAs($user, 'opportunities', ['title' => $opportunity->title, 'version' => $opportunity->version, 'reason' => $reason, 'data' => array_merge($data, ['client_id' => $client->id])], $opportunity->id);

            return $client;
        });
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
