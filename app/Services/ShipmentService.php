<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RegisterTransaction;
use App\Models\Sale;
use App\Models\SalePayment;
use App\Models\Shipment;
use App\Support\BusinessContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Shipments / deliveries. Ports of create_shipment, update_shipment,
 * change_shipment_status, link_shipment_to_sale and delete_shipment.
 */
class ShipmentService
{
    public const STATUSES = ['pending', 'processing', 'shipped', 'in_transit', 'delivered', 'cancelled', 'returned'];

    public function __construct(private readonly RegisterService $registers) {}

    public function create(BusinessContext $ctx, array $input): Shipment
    {
        $ctx->authorize('shipments', 'You do not have permission to create shipments');
        $data = $this->validated($ctx, $input);

        return Shipment::query()->create($data + [
            'business_id' => $ctx->businessId(),
            'shipment_number' => 'SHP-'.now()->format('YmdHis').'-'.strtoupper(Str::substr(str_replace('-', '', (string) Str::uuid()), 0, 6)),
            'delivered_at' => $data['status'] === 'delivered' ? now() : null,
            'created_by' => $ctx->userId(),
        ]);
    }

    public function update(BusinessContext $ctx, string $shipmentId, array $input): Shipment
    {
        $ctx->authorize('shipments', 'You do not have permission to update shipments');
        $data = $this->validated($ctx, $input);

        return DB::transaction(function () use ($ctx, $shipmentId, $data) {
            $shipment = $this->find($ctx, $shipmentId, true);
            $shipment->fill($data);
            $shipment->delivered_at = $data['status'] === 'delivered' ? ($shipment->delivered_at ?? now()) : null;
            $shipment->save();

            return $shipment;
        });
    }

    /**
     * Change status. Delivering a COD shipment collects the outstanding
     * amount as cash into the open register.
     */
    public function changeStatus(BusinessContext $ctx, string $shipmentId, string $status): Shipment
    {
        $ctx->authorize('shipments', 'You do not have permission to change shipment status');
        if (! in_array($status, self::STATUSES, true)) {
            throw new BusinessRuleException("Invalid shipment status: {$status}");
        }

        return DB::transaction(function () use ($ctx, $shipmentId, $status) {
            $businessId = $ctx->businessId();
            $shipment = $this->find($ctx, $shipmentId, true);

            $sale = $shipment->sale_id
                ? Sale::query()->where('business_id', $businessId)->whereKey($shipment->sale_id)->lockForUpdate()->first()
                : null;

            if ($status === 'delivered' && $sale && $this->isCod($sale)) {
                $this->collectCod($ctx, $shipment, $sale);
            }

            $shipment->status = $status;
            $shipment->delivered_at = $status === 'delivered' ? ($shipment->delivered_at ?? now()) : null;
            $shipment->save();

            return $shipment;
        });
    }

    public function linkSale(BusinessContext $ctx, string $shipmentId, string $saleId): Shipment
    {
        $ctx->authorize('shipments', 'You do not have permission to link shipments');

        $shipment = $this->find($ctx, $shipmentId);
        if (! Sale::query()->where('business_id', $ctx->businessId())->whereKey($saleId)->exists()) {
            throw new BusinessRuleException('Sale not found');
        }
        $shipment->update(['sale_id' => $saleId]);

        return $shipment;
    }

    public function delete(BusinessContext $ctx, string $shipmentId): bool
    {
        $ctx->authorize('shipments', 'You do not have permission to delete shipments');

        return Shipment::query()->where('business_id', $ctx->businessId())->whereKey($shipmentId)->delete() > 0;
    }

    private function isCod(Sale $sale): bool
    {
        return SalePayment::query()
            ->where('sale_id', $sale->id)
            ->where('business_id', $sale->business_id)
            ->where('payment_method', 'cod')
            ->exists();
    }

    private function collectCod(BusinessContext $ctx, Shipment $shipment, Sale $sale): void
    {
        $due = max(Money::cents($sale->amount_due), 0);
        if ($due === 0) {
            return;
        }

        $register = $this->registers->lockOpen($ctx->businessId())
            ?? throw new BusinessRuleException('Open a cash register before marking a COD shipment as delivered');

        // Guard against double settlement if the status is set again.
        $alreadyCollected = (int) SalePayment::query()
            ->where('sale_id', $sale->id)
            ->where('payment_method', 'cash')
            ->where('notes', 'like', 'COD COLLECTION%')
            ->get()
            ->sum(fn (SalePayment $p) => Money::cents($p->amount));

        $collect = $due - $alreadyCollected;
        if ($collect <= 0) {
            return;
        }

        SalePayment::query()->create([
            'sale_id' => $sale->id,
            'business_id' => $sale->business_id,
            'payment_method' => 'cash',
            'amount' => Money::decimal($collect),
            'reference_number' => $shipment->tracking_number,
            'notes' => 'COD COLLECTION · Shipment '.$shipment->shipment_number,
        ]);

        $sale->fill([
            'amount_paid' => Money::decimal(Money::cents($sale->amount_paid) + $collect),
            'amount_due' => Money::decimal(max($due - $collect, 0)),
            'payment_status' => 'paid',
        ])->save();

        if ($sale->customer_id) {
            $customer = Customer::query()->where('business_id', $sale->business_id)->whereKey($sale->customer_id)->lockForUpdate()->first();
            if ($customer) {
                $customer->credit_balance = Money::decimal(max(Money::cents($customer->credit_balance) - $collect, 0));
                $customer->save();
            }
        }

        $invoice = Invoice::query()->where('business_id', $sale->business_id)->where('sale_id', $sale->id)->orderBy('created_at')->lockForUpdate()->first();
        if ($invoice) {
            $invoice->fill([
                'status' => 'paid',
                'amount_paid' => Money::decimal(Money::cents($invoice->amount_paid) + $collect),
                'amount_due' => Money::decimal(max(Money::cents($invoice->amount_due) - $collect, 0)),
            ])->save();
        }

        $transaction = RegisterTransaction::query()
            ->where('business_id', $sale->business_id)
            ->where('reference_id', $sale->id)
            ->where('transaction_type', 'cash_sale')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->first();

        if ($transaction && $transaction->register_id === $register->id) {
            $transaction->fill([
                'amount' => Money::decimal(Money::cents($transaction->amount) + $collect),
                'description' => 'COD collection · '.$shipment->shipment_number,
                'created_by' => $ctx->userId(),
            ])->save();
        } else {
            $this->registers->transaction($register, 'cash_sale', $collect, $sale->id, 'COD collection · '.$shipment->shipment_number, $ctx->userId());
        }

        $this->registers->adjust($register, 'cash_sales', $collect);
    }

    private function find(BusinessContext $ctx, string $shipmentId, bool $lock = false): Shipment
    {
        return Shipment::query()
            ->where('business_id', $ctx->businessId())
            ->whereKey($shipmentId)
            ->when($lock, fn ($q) => $q->lockForUpdate())
            ->first() ?? throw new BusinessRuleException('Shipment not found');
    }

    private function validated(BusinessContext $ctx, array $input): array
    {
        $cost = Money::cents($input['shipping_cost'] ?? 0);
        if ($cost < 0) {
            throw new BusinessRuleException('Shipping cost cannot be negative');
        }

        $status = (string) (($input['status'] ?? null) ?: 'pending');
        if (! in_array($status, self::STATUSES, true)) {
            throw new BusinessRuleException("Invalid shipment status: {$status}");
        }

        $customerId = ($input['customer_id'] ?? null) ?: null;
        if ($customerId && ! Customer::query()->where('business_id', $ctx->businessId())->whereKey($customerId)->exists()) {
            throw new BusinessRuleException('Customer not found');
        }

        $saleId = ($input['sale_id'] ?? null) ?: null;
        if ($saleId && ! Sale::query()->where('business_id', $ctx->businessId())->whereKey($saleId)->exists()) {
            throw new BusinessRuleException('Sale not found');
        }

        return [
            'sale_id' => $saleId,
            'customer_id' => $customerId,
            'shipping_date' => $this->date($input['shipping_date'] ?? null),
            'expected_delivery_date' => $this->date($input['expected_delivery_date'] ?? null),
            'shipping_address' => SaleService::nullableText($input['shipping_address'] ?? null),
            'carrier' => SaleService::nullableText($input['carrier'] ?? null),
            'tracking_number' => SaleService::nullableText($input['tracking_number'] ?? null),
            'shipping_cost' => Money::decimal($cost),
            'status' => $status,
            'notes' => SaleService::nullableText($input['notes'] ?? null),
        ];
    }

    private function date(mixed $value): ?CarbonImmutable
    {
        if (! $value) {
            return null;
        }

        try {
            return CarbonImmutable::parse((string) $value)->utc();
        } catch (\Throwable) {
            throw new BusinessRuleException('Enter a valid date');
        }
    }
}
