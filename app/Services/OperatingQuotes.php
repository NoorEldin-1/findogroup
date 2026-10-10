<?php

namespace App\Services;

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
