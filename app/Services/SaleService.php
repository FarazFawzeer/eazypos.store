<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\RegisterTransaction;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Sales: complete, edit, cancel and return.
 * Ports of complete_sale, update_sale, delete_sale and process_sale_return.
 */
class SaleService
{
    public const PAYMENT_METHODS = ['cash', 'card', 'bank_transfer', 'credit', 'advance', 'cheque', 'other', 'cod'];

    public const REFUND_METHODS = ['cash', 'card', 'bank_transfer', 'cheque', 'credit', 'other'];

    public function __construct(
        private readonly DocumentNumberService $numbers,
        private readonly StockLedger $stock,
        private readonly RegisterService $registers,
    ) {}

    /**
     * @param  array{customer_id?: ?string, register_id?: ?string, items: array, payments: array, discount?: mixed, tax?: mixed, shipping_charge?: mixed, other_charge?: mixed, notes?: ?string}  $input
     */
    public function complete(BusinessContext $ctx, array $input): array
    {
        $ctx->authorize('sales', 'You do not have permission to create sales');

        $items = $this->parseItems($input['items'] ?? [], 'A sale must contain at least one product');
        $payments = $this->parsePayments($input['payments'] ?? []);
        [$saleDiscount, $saleTax, $shipping, $other] = $this->charges($input, 'Discount, tax and charges cannot be negative');

        return DB::transaction(function () use ($ctx, $input, $items, $payments, $saleDiscount, $saleTax, $shipping, $other) {
            $businessId = $ctx->businessId();

            $register = null;
            if (! empty($input['register_id'])) {
                $register = $this->registers->lock($businessId, $input['register_id'])
                    ?? throw new BusinessRuleException('Cash register not found');
                if ($register->status !== 'open') {
                    throw new BusinessRuleException('Cash register is not open');
                }
            }

            $customerId = $input['customer_id'] ?? null;
            if ($customerId && ! Customer::query()->where('business_id', $businessId)->whereKey($customerId)->where('is_active', true)->exists()) {
                throw new BusinessRuleException('Customer not found or inactive');
            }

            // Lock every product so two tills can never oversell the same stock.
            $products = $this->stock->lockProducts($businessId, array_column($items, 'product_id'), true);
            $needed = [];
            foreach ($items as $item) {
                $needed[$item['product_id']] = ($needed[$item['product_id']] ?? 0) + $item['quantity'];
            }
            foreach ($needed as $productId => $quantity) {
                $product = StockLedger::find($products, $productId, "Product {$productId} was not found or is inactive");
                if ((float) $product->stock_quantity + 0.0000001 < $quantity) {
                    throw new BusinessRuleException("Insufficient stock for {$product->name}");
                }
            }

            $subtotal = 0;
            $discount = 0;
            $tax = 0;
            foreach ($items as $item) {
                $gross = Money::line($item['quantity'], $item['unit_price']);
                if ($gross - $item['discount'] + $item['tax'] < 0) {
                    throw new BusinessRuleException('Line total cannot be negative');
                }
                $subtotal += $gross;
                $discount += $item['discount'];
                $tax += $item['tax'];
            }

            $discount += $saleDiscount;
            $tax += $saleTax;
            if ($discount > $subtotal) {
                throw new BusinessRuleException('Total discount cannot exceed subtotal');
            }

            $grandTotal = $subtotal - $discount + $tax + $shipping + $other;
            if ($grandTotal < 0) {
                throw new BusinessRuleException('Grand total cannot be negative');
            }

            $paymentTotal = array_sum(array_column($payments, 'amount'));
            $cashPayment = array_sum(array_map(fn ($p) => $p['payment_method'] === 'cash' ? $p['amount'] : 0, $payments));

            $change = 0;
            if ($paymentTotal > $grandTotal) {
                $change = $paymentTotal - $grandTotal;
                if ($cashPayment < $change) {
                    throw new BusinessRuleException('Overpayment/change must be handled through cash');
                }
            }

            $amountPaid = min($paymentTotal, $grandTotal);
            $amountDue = max($grandTotal - $amountPaid, 0);

            if ($amountDue > 0 && ! $customerId) {
                throw new BusinessRuleException('A customer is required for a credit balance');
            }

            $invoiceNumber = $this->numbers->next($businessId, 'invoice');
            $now = now();

            $sale = Sale::query()->create([
                'business_id' => $businessId,
                'customer_id' => $customerId,
                'invoice_number' => $invoiceNumber,
                'sale_date' => $now,
                'payment_status' => $this->saleStatus($amountPaid, $amountDue),
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($amountPaid),
                'amount_due' => Money::decimal($amountDue),
                'change_amount' => Money::decimal($change),
                'status' => 'completed',
                'notes' => $input['notes'] ?? null,
                'created_by' => $ctx->userId(),
            ]);

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => Money::decimal($item['unit_price']),
                    'cost_price' => $product->cost_price,
                    'discount' => Money::decimal($item['discount']),
                    'tax' => Money::decimal($item['tax']),
                    'line_total' => Money::decimal(Money::line($item['quantity'], $item['unit_price']) - $item['discount'] + $item['tax']),
                ]);
                $this->stock->move($product, -$item['quantity'], 'sale', $sale->id, 'Stock reduced by sale', $ctx->userId());
            }

            $this->insertPayments($sale, $payments);

            if ($customerId && $amountDue > 0) {
                $this->adjustCustomerBalance($businessId, $customerId, $amountDue);
            }

            if ($register) {
                // A zero-value entry still links non-cash sales to their register,
                // so edits and deletions can check whether that register is closed.
                $cashNet = max($cashPayment - $change, 0);
                $this->registers->transaction($register, 'cash_sale', $cashNet, $sale->id, 'Cash sale', $ctx->userId());
                $this->registers->adjust($register, 'cash_sales', $cashNet);
            }

            $invoice = Invoice::query()->create([
                'business_id' => $businessId,
                'sale_id' => $sale->id,
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $now,
                'customer_id' => $customerId,
                'status' => $this->invoiceStatus($amountPaid, $amountDue),
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($amountPaid),
                'amount_due' => Money::decimal($amountDue),
                'notes' => $input['notes'] ?? null,
                'created_by' => $ctx->userId(),
            ]);

            return [
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoiceNumber,
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($amountPaid),
                'amount_due' => Money::decimal($amountDue),
                'change_amount' => Money::decimal($change),
            ];
        });
    }

    public function update(BusinessContext $ctx, string $saleId, array $input): array
    {
        $ctx->authorize('sales', 'You do not have permission to edit sales');

        $items = $this->parseItems($input['items'] ?? [], 'At least one sale item is required');
        $payments = $this->parsePayments($input['payments'] ?? []);
        [$saleDiscount, $saleTax, $shipping, $other] = $this->charges($input, 'Discount, tax and charges cannot be negative');

        $productIds = array_column($items, 'product_id');
        if (count($productIds) !== count(array_unique($productIds))) {
            throw new BusinessRuleException('Duplicate products are not allowed in a sale');
        }

        return DB::transaction(function () use ($ctx, $saleId, $input, $items, $payments, $saleDiscount, $saleTax, $shipping, $other) {
            $businessId = $ctx->businessId();

            $sale = Sale::query()->where('business_id', $businessId)->whereKey($saleId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Sale not found');
            if ($sale->status === 'cancelled') {
                throw new BusinessRuleException('Cancelled sales cannot be edited');
            }

            $customerId = $input['customer_id'] ?? null;
            if ($customerId && ! Customer::query()->where('business_id', $businessId)->whereKey($customerId)->where('is_active', true)->exists()) {
                throw new BusinessRuleException('Customer not found or inactive');
            }

            [$register, $saleTransactions, $oldCash] = $this->saleRegister($businessId, $sale, 'Sales from a closed register cannot be edited');
            if (! $register && array_sum(array_map(fn ($p) => $p['payment_method'] === 'cash' ? $p['amount'] : 0, $payments)) > 0) {
                $register = $this->registers->lockOpen($businessId)
                    ?? throw new BusinessRuleException('Open a cash register before editing this sale');
            }

            $oldItems = SaleItem::query()->where('sale_id', $sale->id)->where('business_id', $businessId)->get();
            $products = $this->stock->lockProducts($businessId, array_merge($oldItems->pluck('product_id')->all(), array_column($items, 'product_id')));

            // Put the old quantities back, then check the new ones against that stock.
            $oldQty = [];
            foreach ($oldItems as $old) {
                $oldQty[$old->product_id] = ($oldQty[$old->product_id] ?? 0) + (float) $old->quantity;
                if ($product = $products->get($old->product_id)) {
                    $this->stock->change($product, (float) $old->quantity);
                }
            }

            $newQty = [];
            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                if (! $product || ! $product->is_active) {
                    throw new BusinessRuleException("Product not found or inactive: {$item['product_id']}");
                }
                if ((float) $product->stock_quantity + 0.0000001 < $item['quantity']) {
                    throw new BusinessRuleException("Insufficient stock for product: {$product->name}");
                }
                $newQty[$item['product_id']] = $item['quantity'];
            }

            $subtotal = 0;
            $itemDiscount = 0;
            $itemTax = 0;
            foreach ($items as $item) {
                $subtotal += Money::line($item['quantity'], $item['unit_price']);
                $itemDiscount += $item['discount'];
                $itemTax += $item['tax'];
            }

            $grandTotal = $subtotal - $saleDiscount - $itemDiscount + $saleTax + $itemTax + $shipping + $other;
            if ($grandTotal < 0) {
                throw new BusinessRuleException('Sale total cannot be negative');
            }

            $paymentTotal = array_sum(array_column($payments, 'amount'));
            if ($paymentTotal > $grandTotal) {
                throw new BusinessRuleException('Invalid payment total');
            }
            $newCash = array_sum(array_map(fn ($p) => $p['payment_method'] === 'cash' ? $p['amount'] : 0, $payments));
            $newPaid = $paymentTotal;
            $newDue = max($grandTotal - $newPaid, 0);

            if ($newDue > 0 && ! $customerId) {
                throw new BusinessRuleException('A customer is required for a credit balance');
            }

            foreach ($items as $item) {
                $this->stock->change($products->get($item['product_id']), -$item['quantity']);
            }

            // Net stock effect of the edit, per product (positive = stock returned).
            foreach (array_unique(array_merge(array_keys($oldQty), array_keys($newQty))) as $productId) {
                $delta = Money::qty(($oldQty[$productId] ?? 0) - ($newQty[$productId] ?? 0));
                if ($delta != 0.0) {
                    $this->stock->record($businessId, $productId, $delta > 0 ? 'adjustment_add' : 'adjustment_remove', $delta, $sale->id, 'Sale edited', $ctx->userId());
                }
            }

            SaleItem::query()->where('sale_id', $sale->id)->where('business_id', $businessId)->delete();
            SalePayment::query()->where('sale_id', $sale->id)->where('business_id', $businessId)->delete();

            foreach ($items as $item) {
                $product = $products->get($item['product_id']);
                SaleItem::query()->create([
                    'sale_id' => $sale->id,
                    'business_id' => $businessId,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => Money::decimal($item['unit_price']),
                    'cost_price' => $product->cost_price,
                    'discount' => Money::decimal($item['discount']),
                    'tax' => Money::decimal($item['tax']),
                    'line_total' => Money::decimal(Money::line($item['quantity'], $item['unit_price']) - $item['discount'] + $item['tax']),
                ]);
            }
            $this->insertPayments($sale, $payments);

            $oldCustomerId = $sale->customer_id;
            $oldDue = Money::cents($sale->amount_due);

            $sale->fill([
                'customer_id' => $customerId,
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($saleDiscount + $itemDiscount),
                'tax' => Money::decimal($saleTax + $itemTax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($newPaid),
                'amount_due' => Money::decimal($newDue),
                'change_amount' => '0.00',
                'payment_status' => $this->saleStatus($newPaid, $newDue),
                'notes' => $input['notes'] ?? null,
            ])->save();

            if ($oldCustomerId && $oldDue > 0) {
                $this->adjustCustomerBalance($businessId, $oldCustomerId, -$oldDue);
            }
            if ($customerId && $newDue > 0) {
                $this->adjustCustomerBalance($businessId, $customerId, $newDue);
            }

            if ($register) {
                if ($saleTransactions->isEmpty()) {
                    $this->registers->transaction($register, 'cash_sale', $newCash, $sale->id, 'Sale edited', $ctx->userId());
                } else {
                    $first = $saleTransactions->first();
                    $first->fill(['amount' => Money::decimal($newCash), 'description' => 'Sale edited', 'created_by' => $ctx->userId()])->save();
                    $saleTransactions->slice(1)->each(fn (RegisterTransaction $t) => $t->fill(['amount' => '0.00', 'description' => 'Sale edited'])->save());
                }
                $this->registers->adjust($register, 'cash_sales', $newCash - $oldCash);
            }

            $invoice = Invoice::query()->where('business_id', $businessId)->where('sale_id', $sale->id)->orderBy('created_at')->lockForUpdate()->first()
                ?? new Invoice([
                    'business_id' => $businessId,
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'invoice_date' => $sale->sale_date,
                    'created_by' => $ctx->userId(),
                ]);
            $invoice->fill([
                'customer_id' => $customerId,
                'status' => $this->invoiceStatus($newPaid, $newDue),
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($saleDiscount + $itemDiscount),
                'tax' => Money::decimal($saleTax + $itemTax),
                'shipping_charge' => Money::decimal($shipping),
                'other_charge' => Money::decimal($other),
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($newPaid),
                'amount_due' => Money::decimal($newDue),
                'notes' => $input['notes'] ?? null,
            ])->save();

            return [
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_id' => $invoice->id,
                'invoice_number' => $sale->invoice_number,
                'grand_total' => Money::decimal($grandTotal),
                'amount_paid' => Money::decimal($newPaid),
                'amount_due' => Money::decimal($newDue),
                'payment_status' => $sale->payment_status,
            ];
        });
    }

    /**
     * Cancel a sale: restore stock, reverse customer credit and register cash.
     * The sale and its invoice stay in history with status "cancelled".
     */
    public function delete(BusinessContext $ctx, string $saleId): array
    {
        $ctx->authorize('sales', 'You do not have permission to delete sales');

        return DB::transaction(function () use ($ctx, $saleId) {
            $businessId = $ctx->businessId();

            $sale = Sale::query()->where('business_id', $businessId)->whereKey($saleId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Sale not found');
            if ($sale->status === 'cancelled') {
                throw new BusinessRuleException('Sale is already cancelled');
            }

            [$register, $saleTransactions, $cashAmount] = $this->saleRegister($businessId, $sale, 'Sales from a closed register cannot be deleted');

            $items = SaleItem::query()->where('sale_id', $sale->id)->where('business_id', $businessId)->get();
            $returned = $this->returnedQuantities($businessId, $sale->id);
            $products = $this->stock->lockProducts($businessId, $items->pluck('product_id')->all());

            // Restore only what is still out: quantities already returned are back in stock.
            $restore = [];
            foreach ($items as $item) {
                $restore[$item->product_id] = ($restore[$item->product_id] ?? 0) + (float) $item->quantity;
            }
            foreach ($restore as $productId => $quantity) {
                $quantity = Money::qty($quantity - ($returned[$productId] ?? 0));
                if ($quantity > 0 && ($product = $products->get($productId))) {
                    $this->stock->move($product, $quantity, 'adjustment_add', $sale->id, 'Sale deleted - stock restored', $ctx->userId());
                }
            }

            $oldDue = Money::cents($sale->amount_due);
            if ($sale->customer_id && $oldDue > 0) {
                $this->adjustCustomerBalance($businessId, $sale->customer_id, -$oldDue);
            }

            if ($register) {
                $this->registers->adjust($register, 'cash_sales', -$cashAmount);
                $saleTransactions->each(fn (RegisterTransaction $t) => $t->fill([
                    'amount' => '0.00',
                    'description' => 'Sale deleted - register amount reversed',
                    'created_by' => $ctx->userId(),
                ])->save());
            }

            $sale->fill([
                'status' => 'cancelled',
                'notes' => trim(($sale->notes ? $sale->notes."\n" : '').'Sale deleted/cancelled by user.'),
            ])->save();

            Invoice::query()->where('business_id', $businessId)->where('sale_id', $sale->id)->update(['status' => 'cancelled']);

            return [
                'success' => true,
                'sale_id' => $sale->id,
                'invoice_number' => $sale->invoice_number,
                'restored_cash' => Money::decimal($cashAmount),
                'restored_due' => Money::decimal($oldDue),
            ];
        });
    }

    /**
     * @param  array{items: array, refund_method?: string, register_id?: ?string, notes?: ?string}  $input
     */
    public function processReturn(BusinessContext $ctx, string $saleId, array $input): array
    {
        $refundMethod = $input['refund_method'] ?? 'cash';
        if (! in_array($refundMethod, self::REFUND_METHODS, true)) {
            throw new BusinessRuleException("Invalid refund method: {$refundMethod}");
        }
        $ctx->authorize('sales', 'You do not have permission to process sales returns.');

        $lines = $this->parseReturnLines($input['items'] ?? [], 'At least one return item is required.', 'Return quantity must be greater than zero.');

        return DB::transaction(function () use ($ctx, $saleId, $input, $lines, $refundMethod) {
            $businessId = $ctx->businessId();

            $sale = Sale::query()->where('business_id', $businessId)->whereKey($saleId)->lockForUpdate()->first()
                ?? throw new BusinessRuleException('Sale not found.');
            if ($sale->status === 'cancelled') {
                throw new BusinessRuleException('Cancelled sales cannot be returned.');
            }

            $register = null;
            if ($refundMethod === 'cash') {
                if (empty($input['register_id'])) {
                    throw new BusinessRuleException('An open cash register is required for a cash refund.');
                }
                $register = $this->registers->lock($businessId, $input['register_id'])
                    ?? throw new BusinessRuleException('Cash register not found.');
                if ($register->status !== 'open') {
                    throw new BusinessRuleException('The selected cash register is not open.');
                }
            }

            $saleItems = SaleItem::query()->where('sale_id', $sale->id)->where('business_id', $businessId)->orderBy('created_at')->get();
            $returned = $this->returnedQuantities($businessId, $sale->id);
            $products = $this->stock->lockProducts($businessId, array_keys($lines));

            $returnNumber = $this->numbers->next($businessId, 'sale_return');
            $saleReturn = SaleReturn::query()->create([
                'business_id' => $businessId,
                'sale_id' => $sale->id,
                'customer_id' => $sale->customer_id,
                'return_number' => $returnNumber,
                'return_date' => now(),
                'refund_method' => $refundMethod,
                'status' => 'completed',
                'notes' => $input['notes'] ?? null,
                'created_by' => $ctx->userId(),
            ]);

            $subtotal = 0;
            $discount = 0;
            $tax = 0;
            $grandTotal = 0;

            foreach ($lines as $productId => $quantity) {
                $productLines = $saleItems->where('product_id', $productId);
                $sold = (float) $productLines->sum(fn ($i) => (float) $i->quantity);
                if ($sold <= 0) {
                    throw new BusinessRuleException("Product {$productId} was not part of this sale.");
                }

                $available = Money::qty($sold - ($returned[$productId] ?? 0));
                if ($quantity > $available + 0.0000001) {
                    throw new BusinessRuleException('Cannot return '.Money::plain($quantity).' units of product '.$productId.'. Only '.Money::plain($available).' units are available to return.');
                }

                // The normal sale flow creates one line per product; use the first.
                $original = $productLines->first();
                $originalQty = (float) $original->quantity;
                $unitPrice = Money::cents($original->unit_price);
                $lineDiscount = Money::proportion(Money::cents($original->discount), $quantity, $originalQty);
                $lineTax = Money::proportion(Money::cents($original->tax), $quantity, $originalQty);
                $lineTotal = Money::proportion(Money::cents($original->line_total), $quantity, $originalQty);

                $subtotal += Money::line($quantity, $unitPrice);
                $discount += $lineDiscount;
                $tax += $lineTax;
                $grandTotal += $lineTotal;

                SaleReturnItem::query()->create([
                    'sale_return_id' => $saleReturn->id,
                    'business_id' => $businessId,
                    'product_id' => $productId,
                    'quantity' => $quantity,
                    'unit_price' => Money::decimal($unitPrice),
                    'discount' => Money::decimal($lineDiscount),
                    'tax' => Money::decimal($lineTax),
                    'line_total' => Money::decimal($lineTotal),
                ]);

                $product = StockLedger::find($products, $productId, "Product {$productId} could not be updated.");
                $this->stock->move($product, $quantity, 'sale_return', $saleReturn->id, 'Sale return '.$returnNumber, $ctx->userId());
            }

            $saleReturn->fill([
                'subtotal' => Money::decimal($subtotal),
                'discount' => Money::decimal($discount),
                'tax' => Money::decimal($tax),
                'grand_total' => Money::decimal($grandTotal),
            ])->save();

            if ($register) {
                $this->registers->transaction($register, 'cash_refund', $grandTotal, $saleReturn->id, 'Refund for '.$returnNumber, $ctx->userId());
                $this->registers->adjust($register, 'cash_refunds', $grandTotal);
            }

            return [
                'success' => true,
                'return_id' => $saleReturn->id,
                'return_number' => $returnNumber,
                'sale_id' => $sale->id,
                'grand_total' => Money::decimal($grandTotal),
                'refund_method' => $refundMethod,
                'status' => 'completed',
            ];
        });
    }

    /**
     * The register a sale was taken on, its register transactions and the
     * net cash it put in the drawer. Throws if that register is closed.
     *
     * @return array{0: ?CashRegister, 1: Collection<int, RegisterTransaction>, 2: int}
     */
    private function saleRegister(string $businessId, Sale $sale, string $closedMessage): array
    {
        $transactions = RegisterTransaction::query()
            ->where('business_id', $businessId)
            ->where('reference_id', $sale->id)
            ->where('transaction_type', 'cash_sale')
            ->orderBy('created_at')
            ->lockForUpdate()
            ->get();

        if ($transactions->isEmpty()) {
            return [null, $transactions, 0];
        }

        $register = $this->registers->lock($businessId, $transactions->first()->register_id)
            ?? throw new BusinessRuleException('Register for this sale was not found');
        if ($register->status !== 'open') {
            throw new BusinessRuleException($closedMessage);
        }

        $cash = $transactions->sum(fn (RegisterTransaction $t) => Money::cents($t->amount));

        return [$register, $transactions, (int) $cash];
    }

    /**
     * Quantities already returned per product for a sale.
     *
     * @return array<string, float>
     */
    private function returnedQuantities(string $businessId, string $saleId): array
    {
        return SaleReturnItem::query()
            ->join('sale_returns', 'sale_returns.id', '=', 'sale_return_items.sale_return_id')
            ->where('sale_returns.business_id', $businessId)
            ->where('sale_returns.sale_id', $saleId)
            ->where('sale_returns.status', 'completed')
            ->groupBy('sale_return_items.product_id')
            ->selectRaw('sale_return_items.product_id, SUM(sale_return_items.quantity) AS qty')
            ->pluck('qty', 'product_id')
            ->map(fn ($q) => (float) $q)
            ->all();
    }

    private function adjustCustomerBalance(string $businessId, string $customerId, int $cents): void
    {
        $customer = Customer::query()->where('business_id', $businessId)->whereKey($customerId)->lockForUpdate()->first();
        if (! $customer) {
            return;
        }
        $customer->credit_balance = Money::decimal(max(0, Money::cents($customer->credit_balance) + $cents));
        $customer->save();
    }

    private function insertPayments(Sale $sale, array $payments): void
    {
        foreach ($payments as $payment) {
            SalePayment::query()->create([
                'sale_id' => $sale->id,
                'business_id' => $sale->business_id,
                'payment_method' => $payment['payment_method'],
                'amount' => Money::decimal($payment['amount']),
                'reference_number' => $payment['reference_number'],
                'notes' => $payment['notes'],
            ]);
        }
    }

    private function saleStatus(int $paid, int $due): string
    {
        return $due === 0 ? 'paid' : ($paid > 0 ? 'partial' : 'credit');
    }

    private function invoiceStatus(int $paid, int $due): string
    {
        return $due === 0 ? 'paid' : ($paid > 0 ? 'partially_paid' : 'issued');
    }

    /**
     * @return list<array{product_id: string, quantity: float, unit_price: int, discount: int, tax: int}>
     */
    private function parseItems(mixed $items, string $emptyMessage): array
    {
        if (! is_array($items) || $items === []) {
            throw new BusinessRuleException($emptyMessage);
        }

        $parsed = [];
        foreach ($items as $item) {
            if (! is_array($item) || empty($item['product_id']) || ! is_string($item['product_id'])) {
                throw new BusinessRuleException('Invalid sale item');
            }
            $quantity = Money::qty($item['quantity'] ?? 0);
            $unitPrice = Money::cents($item['unit_price'] ?? 0);
            $discount = Money::cents($item['discount'] ?? 0);
            $tax = Money::cents($item['tax'] ?? 0);

            if ($quantity <= 0) {
                throw new BusinessRuleException('Product quantity must be greater than zero');
            }
            if ($unitPrice < 0) {
                throw new BusinessRuleException('Product price cannot be negative');
            }
            if ($discount < 0 || $tax < 0) {
                throw new BusinessRuleException('Item discount and tax cannot be negative');
            }

            $parsed[] = ['product_id' => $item['product_id'], 'quantity' => $quantity, 'unit_price' => $unitPrice, 'discount' => $discount, 'tax' => $tax];
        }

        return $parsed;
    }

    /**
     * @return list<array{payment_method: string, amount: int, reference_number: ?string, notes: ?string}>
     */
    private function parsePayments(mixed $payments): array
    {
        if (! is_array($payments)) {
            throw new BusinessRuleException('Payments must be a JSON array');
        }

        $parsed = [];
        foreach ($payments as $payment) {
            $method = strtolower(trim((string) ($payment['payment_method'] ?? '')));
            $amount = Money::cents($payment['amount'] ?? 0);

            if ($method === '') {
                throw new BusinessRuleException('Payment method is required');
            }
            if (! in_array($method, self::PAYMENT_METHODS, true)) {
                throw new BusinessRuleException("Invalid payment method: {$method}");
            }
            if ($amount < 0 || ($amount === 0 && ! in_array($method, ['credit', 'advance', 'cod'], true))) {
                throw new BusinessRuleException('Payment amount must be greater than zero');
            }

            $parsed[] = [
                'payment_method' => $method,
                'amount' => $amount,
                'reference_number' => self::nullableText($payment['reference_number'] ?? null),
                'notes' => self::nullableText($payment['notes'] ?? null),
            ];
        }

        return $parsed;
    }

    /**
     * @return array<string, float> product_id => quantity
     */
    private function parseReturnLines(mixed $items, string $emptyMessage, string $qtyMessage): array
    {
        if (! is_array($items) || $items === []) {
            throw new BusinessRuleException($emptyMessage);
        }

        $lines = [];
        foreach ($items as $item) {
            $productId = $item['product_id'] ?? null;
            if (! $productId || ! is_string($productId)) {
                throw new BusinessRuleException('Return item is missing product_id.');
            }
            $quantity = Money::qty($item['quantity'] ?? 0);
            if ($quantity <= 0) {
                throw new BusinessRuleException($qtyMessage);
            }
            $lines[$productId] = Money::qty(($lines[$productId] ?? 0) + $quantity);
        }

        return $lines;
    }

    /**
     * @return array{0: int, 1: int, 2: int, 3: int} discount, tax, shipping, other (cents)
     */
    private function charges(array $input, string $message): array
    {
        $values = [
            Money::cents($input['discount'] ?? 0),
            Money::cents($input['tax'] ?? 0),
            Money::cents($input['shipping_charge'] ?? 0),
            Money::cents($input['other_charge'] ?? 0),
        ];
        foreach ($values as $value) {
            if ($value < 0) {
                throw new BusinessRuleException($message);
            }
        }

        return $values;
    }

    public static function nullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }
}
