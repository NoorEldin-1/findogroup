<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OperatingProcurement
{
    /**
     * Fields a purchase order still accepts after approval: the goods arrive after the commitment is made.
     */
    public const DELIVERY_FIELDS = ['delivered_at', 'delivery_note'];

    /**
     * Invoice states that count against an order or towards payables.
     */
    public const BILLED_STATES = ['draft', 'approved', 'part_paid', 'paid'];

    public function __construct(private OperatingCatalog $catalog, private OperatingWorkflow $workflow) {}

    public function nextOrderNumber(): string
    {
        $prefix = 'PO-'.now()->year.'-';
        $last = DB::table('operating_records')->where('type', 'purchase_orders')->where('data->po_number', 'like', $prefix.'%')->get()
            ->map(fn (object $order): int => (int) substr((string) ($this->catalog->data($order)['po_number'] ?? ''), strlen($prefix)))->max() ?? 0;

        return $prefix.str_pad((string) ($last + 1), 4, '0', STR_PAD_LEFT);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function currency(array $data): string
    {
        return strtoupper(trim((string) ($data['currency'] ?? ''))) ?: 'USD';
    }

    /**
     * @return Collection<int, object>
     */
    public function linked(string $type, string $field, int $targetId): Collection
    {
        return DB::table('operating_records')->where('type', $type)->where('status', '!=', 'archived')
            ->whereIn('id', DB::table('operating_links')->where('field', $field)->where('target_id', $targetId)->select('record_id'))
            ->orderBy('id')->get();
    }

    /**
     * How much of an order is billed and paid. Voided invoices release their share of the order.
     *
     * @return array{amount: float, billed: float, paid: float, remaining: float, invoices: int}
     */
    public function orderCommitment(object $order): array
    {
        $amount = (float) ($this->catalog->data($order)['amount'] ?? 0);
        $invoices = $this->linked('supplier_invoices', 'purchase_order_id', $order->id)->whereIn('status', self::BILLED_STATES);
        $billed = (float) $invoices->sum(fn (object $invoice): float => (float) ($this->catalog->data($invoice)['amount'] ?? 0));
        $paid = (float) $invoices->sum(fn (object $invoice): float => $this->paidAmount($invoice->id));

        return ['amount' => $amount, 'billed' => round($billed, 2), 'paid' => round($paid, 2), 'remaining' => round($amount - $billed, 2), 'invoices' => $invoices->count()];
    }

    /**
     * @param  list<string>  $statuses
     */
    public function paidAmount(int $invoiceId, ?int $exceptPaymentId = null, array $statuses = ['active']): float
    {
        return round((float) $this->linked('supplier_payments', 'supplier_invoice_id', $invoiceId)
            ->whereIn('status', $statuses)
            ->when($exceptPaymentId, fn (Collection $payments) => $payments->where('id', '!=', $exceptPaymentId))
            ->sum(fn (object $payment): float => (float) ($this->catalog->data($payment)['amount'] ?? 0)), 2);
    }

    /**
     * Runs inside a payment transition: the invoice state always mirrors its confirmed payments.
     */
    public function settleInvoice(int $invoiceId, User $user, string $reason): void
    {
        $invoice = DB::table('operating_records')->where('id', $invoiceId)->lockForUpdate()->first();
        if (! $invoice || ! in_array($invoice->status, ['approved', 'part_paid', 'paid'], true)) {
            return;
        }
        $paid = $this->paidAmount($invoice->id);
        $amount = (float) ($this->catalog->data($invoice)['amount'] ?? 0);
        $status = $paid >= $amount - 0.005 ? 'paid' : ($paid > 0 ? 'part_paid' : 'approved');
        if ($status === $invoice->status) {
            return;
        }
        DB::table('operating_records')->where('id', $invoice->id)->update(['status' => $status, 'version' => $invoice->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($invoice->id), 'transition', 'Payment status recalculated: '.$reason, (array) $invoice, ['status' => $status, 'paid' => $paid], $user->id);
    }

    /**
     * Runs inside the invoice approval transaction: a project invoice becomes one actual cost on that project, net of tax.
     */
    public function bookProjectCost(object $invoice, User $user): ?int
    {
        $data = $this->catalog->data($invoice);
        if (empty($data['project_id']) || DB::table('operating_records')->where('type', 'costs')->where('status', '!=', 'archived')->where('data->supplier_invoice_id', $invoice->id)->exists()) {
            return null;
        }
        $supplier = DB::table('operating_records')->find($data['supplier_id']);
        $cost = [
            'project_id' => (int) $data['project_id'],
            'category' => in_array($this->catalog->data($supplier)['category'] ?? null, ['freelancer', 'services'], true) ? 'contractor' : 'tools',
            'actual_cost' => round((float) $data['amount'] - (float) ($data['tax_amount'] ?? 0), 2),
            'remaining_cost' => 0,
            'cost_date' => $data['invoice_date'],
            'supplier_invoice_id' => $invoice->id,
            'evidence' => 'Supplier invoice '.$data['invoice_number'].' ('.$this->currency($data).')',
        ];
        $id = DB::table('operating_records')->insertGetId(['uuid' => Str::uuid()->toString(), 'type' => 'costs', 'title' => $supplier->title.' · '.$data['invoice_number'], 'status' => 'active', 'owner_id' => $user->id, 'created_by' => $user->id, 'data' => json_encode($cost, JSON_UNESCAPED_UNICODE), 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('operating_links')->insert([['record_id' => $id, 'target_id' => $cost['project_id'], 'field' => 'project_id'], ['record_id' => $id, 'target_id' => $invoice->id, 'field' => 'supplier_invoice_id']]);
        $this->workflow->event(DB::table('operating_records')->find($id), 'create', 'Booked from approved supplier invoice '.$data['invoice_number'], null, $cost, $user->id);

        return $id;
    }

    /**
     * Runs inside the void transition: the project no longer carries the cost of a cancelled invoice.
     */
    public function voidProjectCost(object $invoice, User $user, string $reason): void
    {
        foreach (DB::table('operating_records')->where('type', 'costs')->where('status', '!=', 'archived')->where('data->supplier_invoice_id', $invoice->id)->lockForUpdate()->get() as $cost) {
            DB::table('operating_records')->where('id', $cost->id)->update(['status' => 'archived', 'version' => $cost->version + 1, 'updated_at' => now()]);
            $this->workflow->event(DB::table('operating_records')->find($cost->id), 'transition', 'Supplier invoice voided: '.$reason, (array) $cost, ['status' => 'archived'], $user->id);
        }
    }

    /**
     * Runs inside the order approval transaction: the request it fulfils is now ordered.
     */
    public function markRequestOrdered(object $order, User $user): void
    {
        $data = $this->catalog->data($order);
        $request = empty($data['purchase_request_id']) ? null : DB::table('operating_records')->where('id', $data['purchase_request_id'])->lockForUpdate()->first();
        if (! $request || $request->status !== 'approved') {
            return;
        }
        DB::table('operating_records')->where('id', $request->id)->update(['status' => 'ordered', 'version' => $request->version + 1, 'updated_at' => now()]);
        $this->workflow->event(DB::table('operating_records')->find($request->id), 'transition', 'Purchase order '.($data['po_number'] ?? $order->id).' approved', (array) $request, ['status' => 'ordered'], $user->id);
    }
}
