<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class OperatingLeads
{
    /**
     * Column key => Arabic label key for __().
     */
    public const COLUMNS = [
        'title' => 'اسم العميل', 'lead_phone' => 'الهاتف', 'lead_company' => 'الشركة', 'country' => 'الدولة', 'business_profile' => 'نوع النشاط',
        'status' => 'المرحلة', 'lead_quality' => 'جودة العميل', 'fit_status' => 'مدى الملاءمة', 'campaign' => 'الحملة', 'ad_creative' => 'الإعلان',
        'assignee' => 'المسؤول', 'lead_source' => 'المصدر', 'lead_channel' => 'قناة الوارد', 'expected_value' => 'القيمة المتوقعة', 'sla' => 'SLA',
        'created_at' => 'تاريخ الإنشاء', 'first_contact_at' => 'أول تواصل', 'closed_at' => 'تاريخ الإغلاق', 'added_by' => 'أضيف بواسطة',
    ];

    public const DEFAULT_COLUMNS = ['title', 'lead_phone', 'status', 'lead_quality', 'campaign', 'assignee', 'sla', 'created_at'];

    public const PER_PAGE = [25, 50, 100];

    public const EXPORT_LIMIT = 5000;

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingWorkflow $workflow, private Spreadsheet $spreadsheet) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function query(User $user, array $filters): Builder
    {
        $query = $this->access->query($user, 'opportunities')->where('status', '!=', 'archived');
        $search = trim((string) ($filters['q'] ?? ''));
        if ($search !== '') {
            $digits = preg_replace('/\D+/', '', $search);
            $query->where(function (Builder $scope) use ($search, $digits): void {
                $scope->where('title', 'like', '%'.$search.'%')->orWhere('data->lead_company', 'like', '%'.$search.'%')->orWhere('data->lead_contact', 'like', '%'.$search.'%');
                if (strlen($digits) >= 4) {
                    $scope->orWhere('data->phone_key', 'like', '%'.substr($digits, -9).'%');
                }
            });
        }
        $dateColumn = ['contact' => 'data->first_contact_at', 'closed' => 'data->closed_at'][$filters['date_field'] ?? 'created'] ?? null;
        if (! empty($filters['from'])) {
            $dateColumn ? $query->where($dateColumn, '>=', $filters['from']) : $query->where('created_at', '>=', $filters['from'].' 00:00:00');
        }
        if (! empty($filters['to'])) {
            $dateColumn ? $query->where($dateColumn, '<=', $filters['to'].'T23:59') : $query->where('created_at', '<=', $filters['to'].' 23:59:59');
        }
        foreach (['stage' => 'status', 'quality' => 'data->lead_quality', 'fit' => 'data->fit_status', 'source' => 'data->lead_source', 'channel' => 'data->lead_channel'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, $filters[$key]);
            }
        }
        foreach (['campaign' => 'data->campaign_id', 'assignee' => 'data->assignee_id', 'added_by' => 'created_by'] as $key => $column) {
            if (! empty($filters[$key])) {
                $query->where($column, (int) $filters[$key]);
            }
        }
        if (($filters['sla'] ?? null) === 'breached') {
            $query->whereIn('status', OperatingSales::OPEN_STAGES)->where('data->sla_due_at', '<', now()->format('Y-m-d\TH:i'))->whereNull('data->first_contact_at');
        }

        return $query;
    }

    /**
     * @param  iterable<object>  $records
     * @return list<array<string, mixed>>
     */
    public function rows(iterable $records): array
    {
        $records = collect($records);
        $ids = [];
        foreach ($records as $record) {
            $data = $this->catalog->data($record);
            array_push($ids, $data['campaign_id'] ?? null, $data['assignee_id'] ?? null);
        }
        $titles = $this->sales->titles(array_values(array_unique(array_filter($ids))));
        $users = DB::table('users')->whereIn('id', $records->pluck('created_by')->filter()->unique()->all())->pluck('name', 'id')->all();
        $fields = collect($this->catalog->entity('opportunities')['fields'])->keyBy('key');
        $option = fn (string $key, mixed $value): ?string => $value !== null && isset($fields[$key]['options'][$value]) ? $this->catalog->label($fields[$key]['options'][$value]) : ($value === null ? null : (string) $value);
        $moment = fn (?string $value): ?string => $value ? str_replace('T', ' ', $value) : null;

        return $records->map(function (object $record) use ($titles, $users, $option, $moment): array {
            $data = $this->catalog->data($record);

            return [
                'id' => $record->id, 'version' => $record->version, 'title' => $record->title,
                'lead_phone' => $data['lead_phone'] ?? null, 'lead_company' => $data['lead_company'] ?? null, 'country' => $data['country'] ?? null, 'business_profile' => $data['business_profile'] ?? null,
                'status' => $this->catalog->status($record->status), 'status_key' => $record->status,
                'lead_quality' => $option('lead_quality', $data['lead_quality'] ?? null), 'lead_quality_key' => $data['lead_quality'] ?? null,
                'fit_status' => $option('fit_status', $data['fit_status'] ?? null), 'campaign' => $titles[$data['campaign_id'] ?? 0] ?? null, 'ad_creative' => $data['ad_creative'] ?? null,
                'assignee' => $titles[$data['assignee_id'] ?? 0] ?? null, 'lead_source' => $option('lead_source', $data['lead_source'] ?? null), 'lead_channel' => $option('lead_channel', $data['lead_channel'] ?? null),
                'expected_value' => isset($data['expected_value']) && $data['expected_value'] !== '' ? number_format((float) $data['expected_value'], 2).' '.$this->sales->currency($data) : null,
                'sla' => $this->crm->slaState($record), 'created_at' => substr((string) $record->created_at, 0, 16),
                'first_contact_at' => $moment($data['first_contact_at'] ?? null), 'closed_at' => $moment($data['closed_at'] ?? null), 'added_by' => $users[$record->created_by] ?? null,
            ];
        })->values()->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  list<string>  $columns
     */
    public function export(User $user, array $filters, array $columns, bool $maskPhones, string $reason): string
    {
        $records = $this->query($user, $filters)->latest('created_at')->limit(self::EXPORT_LIMIT)->get();
        $sheet = [array_map(fn (string $column): string => __(self::COLUMNS[$column]), $columns)];
        foreach ($this->rows($records) as $row) {
            if ($maskPhones && $row['lead_phone']) {
                $row['lead_phone'] = str_repeat('•', max(0, mb_strlen($row['lead_phone']) - 4)).mb_substr($row['lead_phone'], -4);
            }
            $row['sla'] = $row['sla'] ? __(OperatingCrm::SLA_LABELS[$row['sla']]) : null;
            $sheet[] = array_map(fn (string $column): mixed => $row[$column] ?? null, $columns);
        }
        $this->workflow->event(null, 'export_leads', $reason, null, ['filters' => array_filter($filters), 'columns' => $columns, 'rows' => $records->count(), 'masked' => $maskPhones], $user->id);

        return $this->spreadsheet->xlsx($sheet, 'Leads', app()->getLocale() === 'ar');
    }
}
