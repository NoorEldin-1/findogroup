<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class OperatingQuotes
{
    public function __construct(private OperatingCatalog $catalog, private OperatingAccess $access, private OperatingWorkflow $workflow) {}

    /**
     * Drop rows without a description (blank template rows from the form) and cast the numeric columns.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @return list<array{description: string, kind: string, quantity: float, unit_price: float, discount: float, tax: float}>
     */
    public function normalizeLines(array $rows): array
    {
        $lines = [];
        foreach ($rows as $row) {
            $description = trim((string) ($row['description'] ?? ''));
            if ($description === '') {
                continue;
            }
            $quantity = (float) ($row['quantity'] ?? 0);
            if ($quantity <= 0) {
                $this->workflow->fail('كمية كل بند يجب أن تكون أكبر من صفر.', 'Each line needs a quantity above zero.');
            }
            $lines[] = ['description' => $description, 'kind' => ($row['kind'] ?? 'service') === 'product' ? 'product' : 'service', 'quantity' => $quantity, 'unit_price' => (float) ($row['unit_price'] ?? 0), 'discount' => (float) ($row['discount'] ?? 0), 'tax' => (float) ($row['tax'] ?? 0)];
        }

        return $lines;
    }

    /**
     * Per line: net = qty × price × (1 − discount%), tax = net × tax%. The quote-level discount is a fixed amount taken off the taxed total.
     *
     * @param  list<array{quantity: float, unit_price: float, discount: float, tax: float}>  $lines
     * @return array{subtotal: float, line_discount: float, net: float, tax: float, discount: float, total: float}
     */
    public function totals(array $lines, float $discountAmount = 0): array
    {
        $subtotal = $net = $tax = 0.0;
        foreach ($lines as $line) {
            $gross = $line['quantity'] * $line['unit_price'];
            $lineNet = $gross * (1 - $line['discount'] / 100);
            $subtotal += $gross;
            $net += $lineNet;
            $tax += $lineNet * $line['tax'] / 100;
        }
        if ($discountAmount > round($net + $tax, 2)) {
            $this->workflow->fail('الخصم الإضافي أكبر من إجمالي العرض.', 'The extra discount exceeds the quote total.');
        }

        return ['subtotal' => round($subtotal, 2), 'line_discount' => round($subtotal - $net, 2), 'net' => round($net, 2), 'tax' => round($tax, 2), 'discount' => round($discountAmount, 2), 'total' => round($net + $tax - $discountAmount, 2)];
    }

    /**
     * @return array{quote: object, number: string, issued: string, valid_until: ?string, status: string, draft: bool, client: array{name: ?string, contact: ?string, email: ?string, phone: ?string, address: ?string, tax_number: ?string}, lines: list<array<string, mixed>>, totals: ?array<string, float>, price: float, currency: string, payment_terms: ?string, terms: ?string}
     */
    public function document(User $user, object $quote): array
    {
        $data = $this->catalog->data($quote);
        $opportunity = ! empty($data['opportunity_id']) ? DB::table('operating_records')->find($data['opportunity_id']) : null;
        $lead = $opportunity ? $this->catalog->data($opportunity) : [];
        $client = ! empty($lead['client_id']) && $this->access->can($user, 'clients') ? DB::table('operating_records')->find($lead['client_id']) : null;
        $clientData = $client ? $this->catalog->data($client) : [];

        return [
            'quote' => $quote,
            'number' => $data['quote_number'] ?? 'Q-'.$quote->id,
            'issued' => substr((string) $quote->created_at, 0, 10),
            'valid_until' => $data['valid_until'] ?? null,
            'status' => $quote->status,
            'draft' => $quote->status !== 'approved',
            'client' => [
                'name' => $clientData['company'] ?? $client?->title ?? ($lead['lead_company'] ?? null),
                'contact' => $clientData['contact'] ?? ($lead['lead_contact'] ?? null),
                'email' => $clientData['email'] ?? ($lead['lead_email'] ?? null),
                'phone' => $clientData['phone'] ?? ($lead['lead_phone'] ?? null),
                'address' => $clientData['address'] ?? null,
                'tax_number' => $clientData['tax_number'] ?? null,
            ],
            'lines' => $data['line_items'] ?? [],
            'totals' => $data['totals'] ?? null,
            'price' => (float) ($data['price'] ?? 0),
            'currency' => strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD',
            'payment_terms' => $data['payment_terms'] ?? null,
            'terms' => $data['terms'] ?? null,
        ];
    }

    /**
     * Prefill a project or contract form from an approved quote; the user still reviews and saves it through the normal gates.
     *
     * @return array{title: string, data: array<string, mixed>}
     */
    public function draftFrom(User $user, int $quoteId, string $type): array
    {
        $quote = $this->access->record($user, $quoteId);
        abort_unless($quote->type === 'quotes' && in_array($type, ['projects', 'contracts'], true), 404);
        if ($quote->status !== 'approved') {
            $this->workflow->fail('التحويل متاح للعروض المعتمدة فقط.', 'Only approved quotes can be converted.');
        }
        $data = $this->catalog->data($quote);
        $opportunity = ! empty($data['opportunity_id']) ? DB::table('operating_records')->find($data['opportunity_id']) : null;
        $lead = $opportunity ? $this->catalog->data($opportunity) : [];
        $clientId = $lead['client_id'] ?? null;
        $client = $clientId ? $this->catalog->data(DB::table('operating_records')->find($clientId)) : [];
        $scope = implode("\n", array_map(fn (array $line): string => '- '.$line['description'].' × '.(float) $line['quantity'], $data['line_items'] ?? []));
        if ($type === 'contracts') {
            return ['title' => $quote->title, 'data' => ['client_id' => $clientId, 'quote_id' => $quote->id, 'scope_reference' => trim(($data['quote_number'] ?? '')."\n".$scope)]];
        }
        $contractId = DB::table('operating_records')->where('type', 'contracts')->where('status', 'approved')->where('data->quote_id', $quote->id)->value('id');

        return ['title' => $quote->title, 'data' => ['client_id' => $clientId, 'quote_id' => $quote->id, 'contract_id' => $contractId, 'outcome' => trim(($lead['need'] ?? '')."\n".$scope), 'original_due' => $lead['requested_date'] ?? null, 'client_contact' => $client['contact'] ?? ($lead['lead_contact'] ?? null)]];
    }

    public function convertedProject(int $quoteId): ?object
    {
        return DB::table('operating_records')->where('type', 'projects')->where('status', '!=', 'archived')->where('data->quote_id', $quoteId)->first();
    }

    /**
     * Must run inside the save transaction, after the advisory lock, so two concurrent quotes cannot take the same number.
     */
    public function nextNumber(): string
    {
        $prefix = 'Q-'.now()->year.'-';
        $last = DB::table('operating_records')->where('type', 'quotes')->where('data->quote_number', 'like', $prefix.'%')->get()
            ->map(fn (object $quote): int => (int) substr((string) ($this->catalog->data($quote)['quote_number'] ?? ''), strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }
}
