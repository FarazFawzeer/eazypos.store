<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Purchase;
use App\Models\RegisterTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseExpenseStockTest extends TestCase
{
    use RefreshDatabase;

    private function purchasePayload($supplier, $product, array $overrides = []): array
    {
        return $overrides + [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_cost' => 80, 'selling_price' => 120, 'discount' => 0, 'tax' => 0]],
            'payment_method' => 'cash',
            'amount_paid' => 400,
            'discount' => 0, 'tax' => 0, 'shipping_charge' => 0, 'other_charge' => 0,
            'purchase_date' => now()->toISOString(),
        ];
    }

    public function test_cash_purchase_adds_stock_updates_prices_and_pays_from_register(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 2]);
        $supplier = $this->supplier($business);
        $register = $this->openRegister($owner, 1000);

        $this->actingAs($owner)->postJson('/api/purchases', $this->purchasePayload($supplier, $product, ['register_id' => $register->id]))
            ->assertCreated()->assertJson(['reference_number' => 'PUR-000001', 'grand_total' => '400.00', 'amount_due' => '0.00']);

        $product->refresh();
        $this->assertSame('7.000', $product->stock_quantity);
        $this->assertSame('80.00', $product->cost_price);
        $this->assertSame('120.00', $product->selling_price);
        $this->assertSame('600.00', $register->fresh()->expected_closing_cash);
    }

    public function test_cash_purchase_needs_an_open_register(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $this->actingAs($owner)->postJson('/api/purchases', $this->purchasePayload($this->supplier($business), $this->product($business)))
            ->assertStatus(422)->assertJson(['message' => 'An open register is required for cash purchases']);
    }

    public function test_credit_purchase_edit_delete_and_return_keep_supplier_balance_right(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 0]);
        $supplier = $this->supplier($business);

        $id = $this->actingAs($owner)->postJson('/api/purchases', $this->purchasePayload($supplier, $product, ['payment_method' => 'credit', 'amount_paid' => 100]))
            ->assertCreated()->assertJson(['amount_due' => '300.00'])->json('purchase_id');
        $this->assertSame('300.00', $supplier->fresh()->current_balance);

        // Return 1 unit on credit: owed amount drops by its cost.
        $this->actingAs($owner)->postJson("/api/purchases/{$id}/returns", ['items' => [['product_id' => $product->id, 'quantity' => 1]], 'refund_method' => 'credit'])
            ->assertCreated()->assertJson(['reference_number' => 'PR-000001', 'grand_total' => '80.00']);
        $this->assertSame('220.00', $supplier->fresh()->current_balance);
        $this->assertSame('4.000', $product->fresh()->stock_quantity);

        // Cannot return more than remains.
        $this->actingAs($owner)->postJson("/api/purchases/{$id}/returns", ['items' => [['product_id' => $product->id, 'quantity' => 5]], 'refund_method' => 'credit'])
            ->assertStatus(422);

        // Cancelling removes only the units still in stock from this purchase.
        $this->actingAs($owner)->deleteJson("/api/purchases/{$id}")->assertOk()->assertJson(['status' => 'cancelled']);
        $this->assertSame('0.000', $product->fresh()->stock_quantity);
        $this->assertSame('0.00', $supplier->fresh()->current_balance);
        $this->assertSame('cancelled', Purchase::query()->find($id)->status);
    }

    public function test_purchase_edit_replaces_items(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $a = $this->product($business, ['stock_quantity' => 0]);
        $b = $this->product($business, ['stock_quantity' => 0]);
        $supplier = $this->supplier($business);
        $id = $this->actingAs($owner)->postJson('/api/purchases', $this->purchasePayload($supplier, $a, ['payment_method' => 'card']))->json('purchase_id');

        $this->actingAs($owner)->putJson("/api/purchases/{$id}", [
            'supplier_id' => $supplier->id,
            'items' => [['product_id' => $b->id, 'quantity' => 2, 'unit_cost' => 50, 'selling_price' => 0]],
            'payment_method' => 'card',
            'amount_paid' => 100,
        ])->assertOk()->assertJson(['grand_total' => '100.00']);

        $this->assertSame('0.000', $a->fresh()->stock_quantity);
        $this->assertSame('2.000', $b->fresh()->stock_quantity);
        $this->assertSame('100.00', $b->fresh()->selling_price, 'a zero selling price keeps the existing price');
    }

    public function test_cash_expense_lifecycle_keeps_register_total_correct(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $register = $this->openRegister($owner, 1000);

        $id = $this->actingAs($owner)->postJson('/api/expenses', [
            'description' => 'Transport', 'amount' => 150, 'payment_method' => 'cash', 'expense_date' => '2026-10-16T00:00:00', 'register_id' => $register->id,
        ])->assertCreated()->json('expense_id');
        $this->assertSame('850.00', $register->fresh()->expected_closing_cash);

        // Edit to card: cash comes back to the drawer.
        $this->actingAs($owner)->putJson("/api/expenses/{$id}", ['description' => 'Transport', 'amount' => 200, 'payment_method' => 'card'])->assertOk();
        $this->assertSame('1000.00', $register->fresh()->expected_closing_cash);
        $this->assertSame(0, RegisterTransaction::query()->where('reference_id', $id)->count());

        // Back to cash, then delete.
        $this->actingAs($owner)->putJson("/api/expenses/{$id}", ['description' => 'Transport', 'amount' => 90, 'payment_method' => 'cash', 'register_id' => $register->id])->assertOk();
        $this->assertSame('910.00', $register->fresh()->expected_closing_cash);
        $this->actingAs($owner)->deleteJson("/api/expenses/{$id}")->assertOk();
        $this->assertSame('1000.00', $register->fresh()->expected_closing_cash);
        $this->assertSame(0, Expense::query()->count());
    }

    public function test_expense_categories_are_deactivated_when_used(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $catId = $this->actingAs($owner)->postJson('/api/expense-categories', ['name' => 'Rent'])->assertCreated()->json('category_id');
        $this->actingAs($owner)->postJson('/api/expense-categories', ['name' => 'rent'])->assertStatus(422);

        $this->actingAs($owner)->postJson('/api/expenses', ['category_id' => $catId, 'description' => 'Oct rent', 'amount' => 5000, 'payment_method' => 'bank_transfer'])->assertCreated();
        $this->actingAs($owner)->deleteJson("/api/expense-categories/{$catId}")->assertOk()->assertJson(['deleted' => false, 'deactivated' => true]);
    }

    public function test_stock_adjustment_rules(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 3]);

        $this->actingAs($owner)->postJson("/api/products/{$product->id}/adjust-stock", ['adjustment_type' => 'remove', 'quantity' => 5])
            ->assertStatus(422)->assertJson(['message' => 'Cannot remove 5 units. Current stock is only 3.']);
        $this->actingAs($owner)->postJson("/api/products/{$product->id}/adjust-stock", ['adjustment_type' => 'add', 'quantity' => 2.5, 'reason' => 'Count'])
            ->assertOk()->assertJson(['old_stock' => 3, 'new_stock' => 5.5]);
    }

    public function test_product_save_creates_master_data_and_rejects_duplicate_sku(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $payload = ['name' => 'Tea 100g', 'sku' => 'SKU-TEA', 'category' => 'Beverages', 'brand' => null, 'unit' => 'Pieces', 'cost_price' => 200, 'selling_price' => 260, 'stock_quantity' => 12, 'low_stock_alert' => 5];

        $id = $this->actingAs($owner)->postJson('/api/products', $payload)->assertCreated()->json('id');
        $this->actingAs($owner)->postJson('/api/products', $payload)->assertStatus(422)->assertJson(['message' => 'A product with this SKU or barcode already exists']);

        // Editing the stock writes an adjustment movement.
        $this->actingAs($owner)->putJson("/api/products/{$id}", ['stock_quantity' => 10] + $payload)->assertOk()->assertJson(['stock_quantity' => '10.000']);
        $this->actingAs($owner)->putJson('/api/products/prices', ['prices' => [['id' => $id, 'selling_price' => 275]]])->assertOk()->assertJson(['updated' => 1]);
    }

    public function test_permissions_follow_the_original_rules(): void
    {
        [$business] = $this->makeBusiness();
        $cashier = $this->addStaff($business, ['sales' => true]);
        $product = $this->product($business);

        $register = $this->openRegister($cashier);
        $this->sell($cashier, $register, [[$product, 1]], [['payment_method' => 'cash', 'amount' => 100]])->assertCreated();

        $this->actingAs($cashier)->postJson('/api/purchases', $this->purchasePayload($this->supplier($business), $product))->assertForbidden();
        $this->actingAs($cashier)->postJson('/api/expenses', ['description' => 'x', 'amount' => 1, 'payment_method' => 'card'])->assertForbidden();
        $this->actingAs($cashier)->postJson('/api/suppliers', ['name' => 'Nope'])->assertForbidden();
        $this->actingAs($cashier)->putJson('/api/settings', ['name' => 'Hacked', 'currency' => 'USD'])->assertForbidden();
        $this->actingAs($cashier)->postJson('/api/staff/invitations', ['name' => 'X', 'email' => 'x@example.com', 'role' => 'admin'])->assertForbidden();

        // Cashiers still see products for the POS, but not suppliers or staff.
        $boot = $this->actingAs($cashier)->getJson('/api/bootstrap')->assertOk();
        $this->assertCount(1, $boot->json('products'));
        $this->assertSame([], $boot->json('suppliers'));
        $this->assertCount(1, $boot->json('business_users'));
    }

    public function test_one_business_can_never_touch_another(): void
    {
        [$shopA, $ownerA] = $this->makeBusiness(['name' => 'Shop A']);
        [$shopB, $ownerB] = $this->makeBusiness(['name' => 'Shop B']);
        $productB = $this->product($shopB);
        $registerB = $this->openRegister($ownerB);
        $saleB = $this->sell($ownerB, $registerB, [[$productB, 1]], [['payment_method' => 'cash', 'amount' => 100]])->json('sale_id');
        $customerB = $this->customer($shopB);

        $registerA = $this->openRegister($ownerA);
        $this->sell($ownerA, $registerA, [[$productB, 1]], [['payment_method' => 'cash', 'amount' => 100]])->assertStatus(422);
        $this->actingAs($ownerA)->deleteJson("/api/sales/{$saleB}")->assertStatus(422)->assertJson(['message' => 'Sale not found']);
        $this->actingAs($ownerA)->deleteJson("/api/customers/{$customerB->id}")->assertStatus(404);
        $this->actingAs($ownerA)->postJson("/api/registers/{$registerB->id}/close", ['actual_closing_cash' => 0])->assertStatus(422);

        $boot = $this->actingAs($ownerA)->getJson('/api/bootstrap')->assertOk();
        $this->assertSame([], $boot->json('sales'));
        $this->assertSame('Shop A', $boot->json('account.business'));
        $this->assertSame('9.000', $productB->fresh()->stock_quantity, 'only Shop B\'s own sale changed its stock');
    }
}
