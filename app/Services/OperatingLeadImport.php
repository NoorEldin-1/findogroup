<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class OperatingLeadImport
{
    public const MAX_ROWS = 2000;

    /**
     * Importable lead fields; key => Arabic label key for __().
     */
    public const FIELDS = [
        'title' => 'اسم العميل', 'lead_company' => 'الشركة', 'lead_contact' => 'جهة الاتصال', 'lead_phone' => 'الهاتف', 'lead_email' => 'البريد الإلكتروني',
        'country' => 'الدولة', 'business_profile' => 'نوع النشاط', 'lead_quality' => 'جودة العميل', 'campaign' => 'الحملة', 'ad_creative' => 'الإعلان',
        'status' => 'المرحلة', 'notes' => 'ملاحظات', 'service_introduced' => 'الخدمة المقدمة', 'media_buyer_notes' => 'ملاحظات الميديا باير',
        'lead_date' => 'تاريخ دخول العميل', 'assignee' => 'المسؤول', 'lead_source' => 'المصدر',
    ];

    /**
     * Header words recognised per field; matching is done on normalize()d text, exact first, then the longest contained word.
     */
    public const SYNONYMS = [
        'title' => ['client name', 'lead name', 'full name', 'name', 'اسم العميل', 'الاسم', 'اسم', 'العميل'],
        'lead_company' => ['company', 'company name', 'الشركة', 'اسم الشركة', 'شركة'],
        'lead_contact' => ['contact person', 'contact name', 'جهة الاتصال', 'الشخص المسؤول'],
        'lead_phone' => ['phone', 'phone number', 'mobile', 'whatsapp', 'رقم الهاتف', 'الهاتف', 'هاتف', 'جوال', 'موبايل', 'رقم', 'واتساب'],
        'lead_email' => ['email', 'e-mail', 'البريد', 'البريد الإلكتروني', 'ايميل'],
        'country' => ['country', 'الدولة', 'البلد', 'دولة'],
        'business_profile' => ['business type', 'business profile', 'industry', 'نوع النشاط', 'النشاط', 'القطاع', 'نشاط'],
        'lead_quality' => ['lead quality', 'quality', 'جودة العميل', 'الجودة', 'جودة', 'نوعية'],
        'campaign' => ['campaign', 'campaign name', 'الحملة', 'اسم الحملة', 'حملة'],
        'ad_creative' => ['ad creative', 'creative', 'ad', 'الإعلان', 'إعلان'],
        'status' => ['stage', 'status', 'lead stage', 'المرحلة', 'الحالة', 'مرحلة'],
        'media_buyer_notes' => ['media buyer notes', 'ملاحظات ميديا باير', 'ملاحظات الميديا باير'],
        'service_introduced' => ['service introduced', 'service', 'الخدمة المقدمة', 'الخدمة', 'خدمة'],
        'notes' => ['notes', 'note', 'comments', 'ملاحظات', 'ملاحظة', 'تعليق'],
        'lead_date' => ['lead time', 'lead date', 'date', 'created at', 'وقت العميل', 'تاريخ', 'الوقت', 'وقت'],
        'assignee' => ['sales agent', 'agent', 'owner', 'assigned to', 'المسؤول', 'مسؤول', 'موظف', 'مندوب'],
        'lead_source' => ['lead source', 'source', 'المصدر', 'مصدر'],
    ];

    private const QUALITY_ALIASES = ['hot' => 'hot', 'ساخن' => 'hot', 'warm' => 'warm', 'دافئ' => 'warm', 'cold' => 'cold', 'بارد' => 'cold', 'bad' => 'bad', 'سيئ' => 'bad', 'سيء' => 'bad', 'unknown' => 'unknown', 'غير محدد' => 'unknown'];

    private const STAGE_ALIASES = ['new lead' => 'new', 'meeting scheduled' => 'meeting', 'offer sent' => 'proposal', 'proposal delivered' => 'proposal', 'follow up' => 'contacted', 'contact again' => 'deferred', 'not fit' => 'unsuitable', 'متابعة' => 'contacted', 'تم إرسال العرض' => 'proposal', 'غير مناسب' => 'unsuitable'];

    private const TEXT_FIELDS = ['lead_company', 'lead_contact', 'lead_phone', 'lead_email', 'country', 'business_profile', 'ad_creative', 'notes', 'service_introduced', 'media_buyer_notes'];

    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingCrm $crm, private OperatingSales $sales, private OperatingWorkflow $workflow, private Spreadsheet $spreadsheet) {}

    public function stage(User $user, UploadedFile $file): string
    {
        try {
            $rows = $this->spreadsheet->read($file->getRealPath(), $file->getClientOriginalExtension());
        } catch (RuntimeException $exception) {
            $encoding = $exception->getMessage() === 'encoding';
            $this->workflow->fail($encoding ? 'احفظ ملف CSV بترميز UTF-8 أو ارفعه بصيغة xlsx.' : 'تعذرت قراءة الملف. تأكد أنه CSV أو xlsx سليم.', $encoding ? 'Save the CSV as UTF-8 or upload it as xlsx.' : 'The file could not be read. Make sure it is a valid CSV or xlsx.');
        }
        if (count($rows) < 2) {
            $this->workflow->fail('الملف لا يحتوي صفوف بيانات بعد صف العناوين.', 'The file has no data rows after the header.');
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            $this->workflow->fail('الحد الأقصى '.self::MAX_ROWS.' صف في الملف الواحد. قسّم الملف.', 'The limit is '.self::MAX_ROWS.' rows per file. Split the file.');
        }
        $token = (string) Str::uuid();
        $header = array_shift($rows);
        Storage::disk('local')->put('crm-imports/'.$token.'.json', json_encode(['user_id' => $user->id, 'file' => $file->getClientOriginalName(), 'header' => $header, 'rows' => $rows], JSON_UNESCAPED_UNICODE));

        return $token;
    }

    /**
     * @return array{user_id: int, file: string, header: list<string>, rows: list<list<string>>}
     */
    public function load(User $user, string $token): array
    {
        abort_unless(Str::isUuid($token) && Storage::disk('local')->exists('crm-imports/'.$token.'.json'), 404);
        $staged = json_decode((string) Storage::disk('local')->get('crm-imports/'.$token.'.json'), true);
        abort_unless((int) $staged['user_id'] === $user->id, 403);

        return $staged;
    }

    public function normalize(string $text): string
    {
        $text = strtr(mb_strtolower(trim($text)), ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ئ' => 'ي', 'ؤ' => 'و', 'ـ' => '']);

        return (string) preg_replace('/[\s_\-\/\.:]+/u', '', $text);
    }

    /**
     * @param  list<string>  $header
     * @return array<int, string|null>
     */
    public function guessMapping(array $header): array
    {
        $words = [];
        foreach (self::SYNONYMS as $field => $list) {
            foreach ($list as $word) {
                $words[$field][] = $this->normalize($word);
            }
        }
        $mapping = array_fill(0, count($header), null);
        $used = [];
        foreach ([true, false] as $exact) {
            foreach ($header as $index => $title) {
                $name = $this->normalize((string) $title);
                if ($mapping[$index] !== null || $name === '') {
                    continue;
                }
                $best = null;
                $length = 0;
                foreach ($words as $field => $list) {
                    if (isset($used[$field])) {
                        continue;
                    }
                    foreach ($list as $word) {
                        $hit = $exact ? $name === $word : (mb_strlen($word) >= 3 && str_contains($name, $word));
                        if ($hit && mb_strlen($word) > $length) {
                            [$best, $length] = [$field, mb_strlen($word)];
                        }
                    }
                }
                if ($best) {
                    $mapping[$index] = $best;
                    $used[$best] = true;
                }
            }
        }

        return $mapping;
    }

    /**
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @return array{rows: list<array<string, mixed>>, total: int, duplicates: int}
     */
    public function preview(User $user, string $token, array $mapping, array $options, int $limit = 50): array
    {
        $staged = $this->load($user, $token);
        $lookups = $this->lookups($user);
        $known = $this->knownPhones();
        $rows = [];
        $duplicates = 0;
        foreach ($staged['rows'] as $index => $row) {
            $item = $this->convert($row, $mapping, $options, $lookups);
            $key = $this->crm->phoneKey($item['data']['lead_phone'] ?? null);
            $duplicate = $key !== null && isset($known[$key]);
            if ($key) {
                $known[$key] = true;
            }
            $duplicates += (int) $duplicate;
            if (count($rows) < $limit) {
                $rows[] = ['line' => $index + 2, 'title' => $item['title'], 'phone' => $item['data']['lead_phone'] ?? null, 'quality' => $item['data']['lead_quality'] ?? null, 'stage' => $this->catalog->status($item['stage']), 'campaign' => $this->titleOf($item['data']['campaign_id'] ?? null), 'assignee' => $this->titleOf($item['data']['assignee_id'] ?? null), 'duplicate' => $duplicate, 'warnings' => $item['warnings']];
            }
        }

        return ['rows' => $rows, 'total' => count($staged['rows']), 'duplicates' => $duplicates];
    }

    /**
     * Rows are saved one by one through the normal workflow, so each one is validated and audited and one bad row never blocks the rest.
     *
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @return array{created: int, duplicates: int, failed: list<array{row: int, error: string}>, warnings: list<array{row: int, warning: string}>}
     */
    public function commit(User $user, string $token, array $mapping, array $options): array
    {
        $staged = $this->load($user, $token);
        set_time_limit(300);
        $lookups = $this->lookups($user);
        $known = $this->knownPhones();
        $reason = __('استيراد من ملف').' '.$staged['file'];
        $result = ['created' => 0, 'duplicates' => 0, 'failed' => [], 'warnings' => []];
        foreach ($staged['rows'] as $index => $row) {
            $line = $index + 2;
            $item = $this->convert($row, $mapping, $options, $lookups);
            $key = $this->crm->phoneKey($item['data']['lead_phone'] ?? null);
            if ($key !== null && isset($known[$key])) {
                $result['duplicates']++;

                continue;
            }
            try {
                $lead = $this->sales->saveAs($user, 'opportunities', ['title' => mb_substr($item['title'], 0, 500), 'reason' => $reason, 'data' => $item['data']]);
            } catch (ValidationException $exception) {
                $result['failed'][] = ['row' => $line, 'error' => (string) collect($exception->errors())->flatten()->first()];

                continue;
            }
            if ($key) {
                $known[$key] = true;
            }
            $result['created']++;
            if ($options['skip_sla']) {
                $this->workflow->mark($lead, 'sla_due_at', '', 'Imported without SLA');
            }
            if ($item['stage'] !== 'new') {
                try {
                    $this->sales->transitionAs($user, $lead->id, $item['stage'], $reason);
                } catch (ValidationException $exception) {
                    $item['warnings'][] = __('بقيت المرحلة "جديد"').': '.collect($exception->errors())->flatten()->first();
                }
            }
            foreach ($item['warnings'] as $warning) {
                $result['warnings'][] = ['row' => $line, 'warning' => $warning];
            }
        }
        Storage::disk('local')->delete('crm-imports/'.$token.'.json');
        $this->workflow->event(null, 'import_leads', $reason, null, ['created' => $result['created'], 'duplicates' => $result['duplicates'], 'failed' => count($result['failed'])], $user->id);

        return $result;
    }

    /**
     * @param  list<string>  $row
     * @param  array<int, string|null>  $mapping
     * @param  array{source: string, campaign_id: ?int, assignee_id: ?int, skip_sla: bool}  $options
     * @param  array{campaigns: array<string, int>, employees: array<string, int>}  $lookups
     * @return array{title: string, data: array<string, mixed>, stage: string, warnings: list<string>}
     */
    private function convert(array $row, array $mapping, array $options, array $lookups): array
    {
        $values = [];
        foreach ($mapping as $index => $field) {
            if ($field && trim((string) ($row[$index] ?? '')) !== '') {
                $values[$field] = trim((string) $row[$index]);
            }
        }
        $warnings = [];
        $data = array_intersect_key($values, array_flip(self::TEXT_FIELDS));
        if (isset($data['lead_email']) && ! filter_var($data['lead_email'], FILTER_VALIDATE_EMAIL)) {
            $warnings[] = __('بريد غير صالح تم تجاهله').': '.$data['lead_email'];
            unset($data['lead_email']);
        }
        $data['lead_source'] = $this->optionKey('lead_source', $values['lead_source'] ?? null) ?? $options['source'];
        if (isset($values['lead_quality'])) {
            $quality = collect(self::QUALITY_ALIASES)->mapWithKeys(fn (string $key, string $alias): array => [$this->normalize($alias) => $key])->get($this->normalize($values['lead_quality']));
            $data['lead_quality'] = $quality ?? 'unknown';
            if (! $quality) {
                $warnings[] = __('جودة غير معروفة').': '.$values['lead_quality'];
            }
        }
        $data['campaign_id'] = $options['campaign_id'] ?: ($lookups['campaigns'][$this->normalize($values['campaign'] ?? '')] ?? null);
        if (! $options['campaign_id'] && isset($values['campaign']) && ! $data['campaign_id']) {
            $warnings[] = __('حملة غير موجودة').': '.$values['campaign'];
        }
        $data['assignee_id'] = isset($values['assignee']) ? ($lookups['employees'][$this->normalize($values['assignee'])] ?? null) : null;
        if (isset($values['assignee']) && ! $data['assignee_id']) {
            $warnings[] = __('مسؤول غير موجود').': '.$values['assignee'];
        }
        $data['assignee_id'] ??= $options['assignee_id'];
        if (isset($values['lead_date'])) {
            $data['lead_date'] = $this->moment($values['lead_date']);
            if (! $data['lead_date']) {
                $warnings[] = __('تاريخ غير مفهوم').': '.$values['lead_date'];
            }
        }
        $stage = 'new';
        if (isset($values['status'])) {
            $stage = $this->stageKey($values['status']) ?? 'new';
            if (! $this->stageKey($values['status'])) {
                $warnings[] = __('مرحلة غير معروفة').': '.$values['status'];
            }
        }

        return ['title' => $values['title'] ?? $values['lead_company'] ?? $values['lead_phone'] ?? __('عميل مستورد'), 'data' => array_filter($data, fn (mixed $value): bool => $value !== null && $value !== ''), 'stage' => $stage, 'warnings' => $warnings];
    }

    /**
     * @return array{campaigns: array<string, int>, employees: array<string, int>}
     */
    private function lookups(User $user): array
    {
        $index = fn ($rows): array => collect($rows)->mapWithKeys(fn (string $title, int|string $id): array => [$this->normalize($title) => (int) $id])->all();

        return [
            'campaigns' => $this->access->can($user, 'campaigns') ? $index($this->access->query($user, 'campaigns')->where('status', '!=', 'archived')->pluck('title', 'id')) : [],
            'employees' => $index($this->access->query($user, 'employees')->where('status', 'active')->pluck('title', 'id')),
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function knownPhones(): array
    {
        $known = [];
        foreach (DB::table('operating_records')->where('type', 'opportunities')->where('status', '!=', 'archived')->pluck('data') as $data) {
            $key = json_decode($data, true)['phone_key'] ?? null;
            if ($key) {
                $known[$key] = true;
            }
        }

        return $known;
    }

    private function optionKey(string $field, ?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $wanted = $this->normalize($value);
        foreach (collect($this->catalog->entity('opportunities')['fields'])->firstWhere('key', $field)['options'] as $key => $label) {
            if (in_array($wanted, [$this->normalize($key), $this->normalize($label['ar']), $this->normalize($label['en'])], true)) {
                return $key;
            }
        }

        return null;
    }

    private function stageKey(string $value): ?string
    {
        $wanted = $this->normalize($value);
        foreach (self::STAGE_ALIASES as $alias => $status) {
            if ($this->normalize($alias) === $wanted) {
                return $status;
            }
        }
        foreach ($this->catalog->entity('opportunities')['statuses'] as $status) {
            $label = config('operating.statuses.'.$status, []);
            if ($status !== 'archived' && in_array($wanted, [$this->normalize($status), $this->normalize($label['ar'] ?? ''), $this->normalize($label['en'] ?? '')], true)) {
                return $status;
            }
        }

        return null;
    }

    /**
     * Excel stores dates as day serials since 1899-12-30; text dates are parsed as written.
     */
    private function moment(string $value): ?string
    {
        try {
            if (is_numeric($value) && (float) $value > 20000 && (float) $value < 80000) {
                return CarbonImmutable::create(1899, 12, 30)->addSeconds((int) round((float) $value * 86400))->format('Y-m-d\TH:i');
            }

            return CarbonImmutable::parse($value)->format('Y-m-d\TH:i');
        } catch (Throwable) {
            return null;
        }
    }

    private function titleOf(mixed $id): ?string
    {
        return $id ? ($this->sales->titles([(int) $id])[(int) $id] ?? null) : null;
    }
}
