<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Supplier;
use App\Support\BusinessContext;
use App\Support\Money;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Purchases: create, edit, cancel and return to supplier.
 * Ports of create_purchase, update_purchase, delete_purchase and process_purchase_return.
 */
class PurchaseService
{
    public const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'cheque', 'credit', 'other'];

    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly StockLedger $stock,
        private readonly RegisterService $registers,
    ) {}

    public function create(BusinessContext $ctx, array $input): array
    {
        $ctx->authorize('purchases', 'You do not have permission to create purchases');

        $items = $this->parseItems($input['items'] ?? []);
        $method = $this->paymentMethod($input['payment_method'] ?? 'cash', true);
        $paid = Money::cents($input['amount_paid'] ?? 0);
        [$discount, $tax, $shipping, $other] = $this->charges($input);
        if ($paid < 0) {
            throw new BusinessRuleException('Amount paid cannot be negative');
        }

        return DB::transaction(function () use ($ctx, $input, $items, $method, $paid, $discount, $tax, $shipping, $other) {
            $businessId = $ctx->businessId();
            $supplierId = $this->validSupplier($businessId, $input['supplier_id'] ?? null);
            $products = $this->stock->lockProducts($businessId, array_column($items, 'product_id'));

            $subtotal = 0;
            foreach ($items as $item) {
                StockLedger::find($products, $item['product_id'], "Product not found: {$item['product_id']}");
                $subtotal += $item['line_total'];
            }

            $grandTotal = $subtotal - $discount + $tax + $shipping + $other;
            if ($grandTotal < 0) {
                throw new BusinessRuleException('Purchase grand total cannot be negative');
            }
            if ($paid > $grandTotal) {
                throw new BusinessRuleException('Amount paid cannot be greater than purchase total');
            }

            $due = $grandTotal - $paid;
            if ($method === 'credit' && $due <= 0) {
                throw new BusinessRuleException('Credit purchase must have an outstanding amount');
            }
            if ($method !== 'credit' && $due > 0) {
                throw new BusinessRuleException('Outstanding balance requires Credit payment method');
            }

            $register = null;
            if ($method === 'cash' && $paid > 0) {
                $register = $this->registers->lockOpenById($businessId, $input['register_id'] ?? null, 'An open register is required for cash purchases');
            }

            $reference = $this->numbers->next($businessId, 'purchase');
            $purchase = Purchase::query()->create([
                'business_id' => $businessId,
                'supplier_id' => $supplierId,
                'reference_number' => $reference,
                'purchase_date' => $this->date($input['purchase_date'] ?? null),
                'payment_method' => $method,
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($paid),
                'amount_due' => Money::decimal($due),
                'status' => 'completed',
                'notes' => SaleService::nullableText($input['notes'] ?? null),
                'created_by' => $ctx->userId(),
            ]);

            $this->applyItems($ctx, $purchase, $items, $products, 'Purchase '.$reference);

            if ($supplierId && $due > 0) {
                $this->adjustSupplierBalance($businessId, $supplierId, $due);
            }

            if ($register) {
                $this->registers->transaction($register, 'cash_out', $paid, $purchase->id, 'Purchase '.$reference, $ctx->userId());
                $this->registers->adjust($register, 'cash_outflows', $paid);
            }

            return [
                'success' => true,
                'purchase_id' => $purchase->id,
                'reference_number' => $reference,
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($paid),
                'amount_due' => Money::decimal($due),
                'payment_method' => $method,
                'status' => 'completed',
            ];
        });
    }

    public function update(BusinessContext $ctx, string $purchaseId, array $input): array
    {
        $ctx->authorize('purchases', 'You do not have permission to edit purchases');

        $method = $this->paymentMethod($input['payment_method'] ?? 'cash', false);
        $items = $this->parseItems($input['items'] ?? []);
        $paid = Money::cents($input['amount_paid'] ?? 0);
        [$discount, $tax, $shipping, $other] = $this->charges($input);
        if ($paid < 0) {
            throw new BusinessRuleException('Amount paid cannot be negative');
        }

        return DB::transaction(function () use ($ctx, $purchaseId, $input, $items, $method, $paid, $discount, $tax, $shipping, $other) {
            $businessId = $ctx->businessId();

            $purchase = Purchase::query()->where('business_id', $businessId)->whereKey($purchaseId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Purchase not found');
            if ($purchase->status === 'cancelled') {
                throw new BusinessRuleException('Cancelled purchases cannot be edited');
            }

            $supplierId = $this->validSupplier($businessId, $input['supplier_id'] ?? null);
            $reference = $purchase->reference_number;
            $oldItems = PurchaseItem::query()->where('purchase_id', $purchase->id)->where('business_id', $businessId)->get();
            $products = $this->stock->lockProducts($businessId, array_merge($oldItems->pluck('product_id')->all(), array_column($items, 'product_id')));

            // Reverse the previous purchase completely.
            foreach ($oldItems as $old) {
                if ($product = $products->get($old->product_id)) {
                    $this->stock->move($product, -(float) $old->quantity, 'adjustment_remove', $purchase->id, 'Edit reversal of purchase '.$reference, $ctx->userId());
                }
            }

            $oldDue = Money::cents($purchase->amount_due);
            if ($purchase->supplier_id && $oldDue > 0) {
                $this->adjustSupplierBalance($businessId, $purchase->supplier_id, -$oldDue);
            }

            $register = null;
            $oldPaid = Money::cents($purchase->amount_paid);
            if ($purchase->payment_method === 'cash' && $oldPaid > 0) {
                $register = $this->registers->lockOpen($businessId)
                    ?? throw new BusinessRuleException('An open register is required to edit a cash purchase');
                $this->registers->transaction($register, 'cash_in', $oldPaid, $purchase->id, 'Edit reversal of purchase '.$reference, $ctx->userId());
                $this->registers->adjust($register, 'cash_outflows', -$oldPaid);
            }

            PurchaseItem::query()->where('purchase_id', $purchase->id)->where('business_id', $businessId)->delete();

            $subtotal = 0;
            foreach ($items as $item) {
                StockLedger::find($products, $item['product_id'], 'Product not found');
                $subtotal += $item['line_total'];
            }
            $grandTotal = $subtotal - $discount + $tax + $shipping + $other;
            if ($grandTotal < 0) {
                throw new BusinessRuleException('Purchase total cannot be negative');
            }

            if ($method === 'credit') {
                $due = $grandTotal - $paid;
            } else {
                if ($paid !== $grandTotal) {
                    throw new BusinessRuleException('Payment amount must equal total');
                }
                $due = 0;
            }
            if ($due < 0) {
                throw new BusinessRuleException('Amount paid cannot exceed total');
            }

            $this->applyItems($ctx, $purchase, $items, $products, 'Purchase edit '.$reference);

            if ($supplierId && $due > 0) {
                $this->adjustSupplierBalance($businessId, $supplierId, $due);
            }

            if ($method === 'cash' && $paid > 0) {
                $register ??= $this->registers->lockOpen($businessId)
                    ?? throw new BusinessRuleException('An open register is required for cash purchases');
                $this->registers->transaction($register, 'cash_out', $paid, $purchase->id, 'Purchase edit '.$reference, $ctx->userId());
                $this->registers->adjust($register, 'cash_outflows', $paid);
            }

            $purchase->fill([
                'supplier_id' => $supplierId,
                'purchase_date' => $this->date($input['purchase_date'] ?? null),
                'payment_method' => $method,
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($paid),
                'amount_due' => Money::decimal($due),
                'status' => 'completed',
                'notes' => SaleService::nullableText($input['notes'] ?? null),
            ])->save();

            return [
                'success' => true,
                'purchase_id' => $purchase->id,
                'reference_number' => $reference,
                'grand_total' => Money::decimal($grandTotal),
                'amount_due' => Money::decimal($due),
            ];
        });
    }

    /**
     * Cancel a purchase and reverse its stock, supplier balance and cash.
     */
    public function delete(BusinessContext $ctx, string $purchaseId): array
    {
        $ctx->authorize('purchases', 'You do not have permission to delete purchases');

        return DB::transaction(function () use ($ctx, $purchaseId) {
            $businessId = $ctx->businessId();

            $purchase = Purchase::query()->where('business_id', $businessId)->whereKey($purchaseId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Purchase not found');
            if ($purchase->status === 'cancelled') {
                throw new BusinessRuleException('Purchase is already cancelled');
            }
            $reference = $purchase->reference_number;

            $paid = Money::cents($purchase->amount_paid);
            $register = null;
            if ($purchase->payment_method === 'cash' && $paid > 0) {
                $register = $this->registers->lockOpen($businessId)
                    ?? throw new BusinessRuleException('An open register is required to reverse this cash purchase');
            }

            $items = PurchaseItem::query()->where('purchase_id', $purchase->id)->where('business_id', $businessId)->get();
            $returned = $this->returnedQuantities($businessId, $purchase->id);
            $products = $this->stock->lockProducts($businessId, $items->pluck('product_id')->all());

            // Quantities already sent back to the supplier have left stock once already.
            foreach ($items->groupBy('product_id') as $productId => $lines) {
                $quantity = Money::qty($lines->sum(fn ($l) => (float) $l->quantity) - ($returned[$productId] ?? 0));
                if ($quantity > 0 && ($product = $products->get($productId))) {
                    $this->stock->move($product, -$quantity, 'adjustment_remove', $purchase->id, 'Reversal of purchase '.$reference, $ctx->userId());
                }
            }

            $due = Money::cents($purchase->amount_due);
            if ($purchase->supplier_id && $due > 0) {
                $this->adjustSupplierBalance($businessId, $purchase->supplier_id, -$due);
            }

            if ($register) {
                $this->registers->transaction($register, 'cash_in', $paid, $purchase->id, 'Reversal of purchase '.$reference, $ctx->userId());
                $this->registers->adjust($register, 'cash_outflows', -$paid);
            }

            $purchase->status = 'cancelled';
            $purchase->save();

            return ['success' => true, 'purchase_id' => $purchase->id, 'reference_number' => $reference, 'status' => 'cancelled'];
        });
    }

    public function processReturn(BusinessContext $ctx, string $purchaseId, array $input): array
    {
        $refundMethod = $input['refund_method'] ?? 'credit';
        $ctx->authorize('purchases', 'You do not have permission to process purchase returns');
        if (! in_array($refundMethod, self::PAYMENT_METHODS, true)) {
            throw new BusinessRuleException("Invalid refund method: {$refundMethod}");
        }

        $lines = $this->parseReturnLines($input['items'] ?? []);

        return DB::transaction(function () use ($ctx, $purchaseId, $input, $lines, $refundMethod) {
            $businessId = $ctx->businessId();

            $purchase = Purchase::query()->where('business_id', $businessId)->whereKey($purchaseId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Purchase not found');
            if ($purchase->status !== 'completed') {
                throw new BusinessRuleException('Only completed purchases can be returned');
            }

            $products = $this->stock->lockProducts($businessId, array_keys($lines));
            $purchaseItems = PurchaseItem::query()->where('purchase_id', $purchase->id)->where('business_id', $businessId)->get();
            $returned = $this->returnedQuantities($businessId, $purchase->id);

            $rows = [];
            $grandTotal = 0;
            foreach ($lines as $productId => $quantity) {
                StockLedger::find($products, $productId, "Product not found: {$productId}");
                $original = $purchaseItems->firstWhere('product_id', $productId)
                    ?? throw new BusinessRuleException('Product was not part of this purchase');

                $originalQty = (float) $original->quantity;
                $remaining = Money::qty($originalQty - ($returned[$productId] ?? 0));
                if ($quantity > $remaining + 0.0000001) {
                    throw new BusinessRuleException('Cannot return '.Money::plain($quantity).' units of product '.$productId.'. Only '.Money::plain($remaining).' units remain available for return.');
                }

                $row = [
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_cost' => Money::cents($original->unit_cost),
                    'discount' => Money::proportion(Money::cents($original->discount), $quantity, $originalQty),
                    'tax' => Money::proportion(Money::cents($original->tax), $quantity, $originalQty),
                    'line_total' => Money::proportion(Money::cents($original->line_total), $quantity, $originalQty),
                ];
                $grandTotal += $row['line_total'];
                $rows[] = $row;
            }

            if ($grandTotal <= 0) {
                throw new BusinessRuleException('Purchase return total must be greater than zero');
            }

            $register = null;
            if ($refundMethod === 'cash') {
                $register = $this->registers->lockOpenById($businessId, $input['register_id'] ?? null, 'An open register is required for a cash refund');
            }

            $reference = $this->numbers->next($businessId, 'purchase_return');
            $purchaseReturn = PurchaseReturn::query()->create([
                'business_id' => $businessId,
                'supplier_id' => $purchase->supplier_id,
                'purchase_id' => $purchase->id,
                'reference_number' => $reference,
                'return_date' => now(),
                'subtotal' => Money::decimal($grandTotal),
                'discount' => '0.00',
                'tax' => '0.00',
                'other_charge' => '0.00',
                'grand_total' => Money::decimal($grandTotal),
                'refund_method' => $refundMethod,
                'status' => 'completed',
                'notes' => SaleService::nullableText($input['notes'] ?? null),
                'created_by' => $ctx->userId(),
            ]);

            foreach ($rows as $row) {
                PurchaseReturnItem::query()->create([
                    'purchase_return_id' => $purchaseReturn->id,
                    'business_id' => $businessId,
                    'product_id' => $row['product_id'],
                    'quantity' => $row['quantity'],
                    'unit_cost' => Money::decimal($row['unit_cost']),
                    'discount' => Money::decimal($row['discount']),
                    'tax' => Money::decimal($row['tax']),
                    'line_total' => Money::decimal($row['line_total']),
                ]);
                $this->stock->move($products->get($row['product_id']), -$row['quantity'], 'purchase_return', $purchaseReturn->id, 'Purchase Return '.$reference, $ctx->userId());
            }

            // A credit refund reduces what the business owes the supplier.
            if ($purchase->supplier_id && $refundMethod === 'credit') {
                $this->adjustSupplierBalance($businessId, $purchase->supplier_id, -$grandTotal);
            }

            // A cash refund is money coming back into the drawer.
            if ($register) {
                $this->registers->transaction($register, 'cash_in', $grandTotal, $purchaseReturn->id, 'Purchase Return '.$reference, $ctx->userId());
                $this->registers->adjust($register, 'cash_inflows', $grandTotal);
            }

            return [
                'success' => true,
                'purchase_return_id' => $purchaseReturn->id,
                'reference_number' => $reference,
                'purchase_id' => $purchase->id,
                'grand_total' => Money::decimal($grandTotal),
                'refund_method' => $refundMethod,
                'status' => 'completed',
            ];
        });
    }

    private function applyItems(BusinessContext $ctx, Purchase $purchase, array $items, $products, string $note): void
    {
        foreach ($items as $item) {
            $product = $products->get($item['product_id']);

            PurchaseItem::query()->create([
                'purchase_id' => $purchase->id,
                'business_id' => $purchase->business_id,
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'unit_cost' => Money::decimal($item['unit_cost']),
                'selling_price' => Money::decimal($item['selling_price']),
                'discount' => Money::decimal($item['discount']),
                'tax' => Money::decimal($item['tax']),
                'line_total' => Money::decimal($item['line_total']),
            ]);

            // Latest purchase cost becomes the product cost; a selling price is only overwritten when given.
            $product->cost_price = Money::decimal($item['unit_cost']);
            if ($item['selling_price'] > 0) {
                $product->selling_price = Money::decimal($item['selling_price']);
            }
            $this->stock->move($product, $item['quantity'], 'purchase', $purchase->id, $note, $ctx->userId());
        }
    }

    /**
     * @return array<string, float>
     */
    private function returnedQuantities(string $businessId, string $purchaseId): array
    {
        return PurchaseReturnItem::query()
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->where('purchase_returns.business_id', $businessId)
            ->where('purchase_returns.purchase_id', $purchaseId)
            ->where('purchase_returns.status', 'completed')
            ->groupBy('purchase_return_items.product_id')
            ->selectRaw('purchase_return_items.product_id, SUM(purchase_return_items.quantity) AS qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($q) => (float) $q)
            ->all();
    }

    private function adjustSupplierBalance(string $businessId, string $supplierId, int $cents): void
    {
        $supplier = Supplier::query()->where('business_id', $businessId)->whereKey($supplierId)->lockForUpdate()->first();
        if (! $supplier) {
            return;
        }
        $supplier->current_balance = Money::decimal(max(0, Money::cents($supplier->current_balance) + $cents));
        $supplier->save();
    }

    private function validSupplier(string $businessId, ?string $supplierId): ?string
    {
        if (! $supplierId) {
            return null;
        }
        if (! Supplier::query()->where('business_id', $businessId)->whereKey($supplierId)->where('is_active', true)->exists()) {
            throw new BusinessRuleException('Supplier not found or inactive');
        }

        return $supplierId;
    }

    private function paymentMethod(mixed $method, bool $detailed): string
    {
        $method = (string) $method;
        if (! in_array($method, self::PAYMENT_METHODS, true)) {
            throw new BusinessRuleException($detailed ? "Invalid payment method: {$method}" : 'Invalid payment method');
        }

        return $method;
    }

    private function date(mixed $value): CarbonImmutable
    {
        if (! $value) {
            return CarbonImmutable::now();
        }

        try {
            return CarbonImmutable::parse((string) $value)->utc();
        } catch (\Throwable) {
            throw new BusinessRuleException('Enter a valid purchase date');
        }
    }

    /**
     * @return list<array{product_id: string, quantity: float, unit_cost: int, selling_price: int, discount: int, tax: int, line_total: int}>
     */
    private function parseItems(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            throw new BusinessRuleException('At least one purchase item is required');
        }

        $parsed = [];
        $seen = [];
        foreach ($items as $item) {
            $productId = is_array($item) ? ($item['product_id'] ?? null) : null;
            if (! $productId || ! is_string($productId)) {
                throw new BusinessRuleException('Product ID is required');
            }
            if (isset($seen[$productId])) {
                throw new BusinessRuleException('Duplicate products are not allowed in the same purchase');
            }
            $seen[$productId] = true;

            $row = [
                'product_id' => $productId,
                'quantity' => Money::qty($item['quantity'] ?? 0),
                'unit_cost' => Money::cents($item['unit_cost'] ?? 0),
                'selling_price' => Money::cents($item['selling_price'] ?? 0),
                'discount' => Money::cents($item['discount'] ?? 0),
                'tax' => Money::cents($item['tax'] ?? 0),
            ];

            if ($row['quantity'] <= 0) {
                throw new BusinessRuleException('Purchase quantity must be greater than zero');
            }
            if ($row['unit_cost'] < 0) {
                throw new BusinessRuleException('Unit cost cannot be negative');
            }
            if ($row['selling_price'] < 0) {
                throw new BusinessRuleException('Selling price cannot be negative');
            }
            if ($row['discount'] < 0) {
                throw new BusinessRuleException('Item discount cannot be negative');
            }
            if ($row['tax'] < 0) {
                throw new BusinessRuleException('Item tax cannot be negative');
            }

            $row['line_total'] = Money::line($row['quantity'], $row['unit_cost']) - $row['discount'] + $row['tax'];
            if ($row['line_total'] < 0) {
                throw new BusinessRuleException('Purchase line total cannot be negative');
            }
            $parsed[] = $row;
        }

        return $parsed;
    }

    /**
     * @return array<string, float>
     */
    private function parseReturnLines(mixed $items): array
    {
        if (! is_array($items) || $items === []) {
            throw new BusinessRuleException('At least one purchase return item is required');
        }

        $lines = [];
        foreach ($items as $item) {
            $productId = is_array($item) ? ($item['product_id'] ?? null) : null;
            if (! $productId || ! is_string($productId)) {
                throw new BusinessRuleException('Product ID is required');
            }
            if (isset($lines[$productId])) {
                throw new BusinessRuleException('Duplicate products are not allowed in the same purchase return');
            }
            $quantity = Money::qty($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new BusinessRuleException('Return quantity must be greater than zero');
            }
            $lines[$productId] = $quantity;
        }

        return $lines;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int}
     */
    private function charges(array $input): array
    {
        $labels = ['discount' => 'Discount', 'tax' => 'Tax', 'shipping_charge' => 'Shipping charge', 'other_charge' => 'Other charge'];
        $values = [];
        foreach ($labels as $key => $label) {
            $value = Money::cents($input[$key] ?? 0);
            if ($value < 0) {
                throw new BusinessRuleException("{$label} cannot be negative");
            }
            $values[] = $value;
        }

        return $values;
    }
}
