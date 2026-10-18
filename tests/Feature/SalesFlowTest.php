<?php

namespace Tests\Feature;

use App\Models\CashRegister;
use App\Models\Invoice;
use App\Models\Sale;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_sale_reduces_stock_posts_cash_and_creates_invoice(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 10, 'selling_price' => 125.50]);
        $register = $this->openRegister($owner, 1000);

        $response = $this->sell($owner, $register, [[$product, 2]], [['payment_method' => 'cash', 'amount' => 251]])
            ->assertCreated()
            ->assertJson(['success' => true, 'invoice_number' => 'INV-000001', 'grand_total' => '251.00', 'amount_due' => '0.00']);

        $this->assertSame('8.000', $product->fresh()->stock_quantity);
        $register->refresh();
        $this->assertSame('251.00', $register->cash_sales);
        $this->assertSame('1251.00', $register->expected_closing_cash);

        $sale = Sale::query()->findOrFail($response->json('sale_id'));
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('paid', Invoice::query()->where('sale_id', $sale->id)->value('status'));
        $this->assertSame(-2.0, (float) StockMovement::query()->where('reference_id', $sale->id)->value('quantity'));

        // Numbers keep counting per business.
        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'card', 'amount' => 125.50]])
            ->assertJson(['invoice_number' => 'INV-000002']);
    }

    public function test_credit_sale_with_part_payment_tracks_customer_due(): void
    {
        // README check: Credit, total 250, paid now 50 => paid 50, due 200.
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['selling_price' => 250]);
        $customer = $this->customer($business);
        $register = $this->openRegister($owner);

        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'credit', 'amount' => 50, 'notes' => 'Customer credit sale']], ['customer_id' => $customer->id])
            ->assertCreated()
            ->assertJson(['amount_paid' => '50.00', 'amount_due' => '200.00']);

        $this->assertSame('200.00', $customer->fresh()->credit_balance);
        $this->assertSame('partial', Sale::query()->value('payment_status'));
        // Credit payments are not cash, so the drawer is unchanged.
        $this->assertSame('0.00', $register->fresh()->cash_sales);
    }

    public function test_credit_sale_with_nothing_paid_is_marked_credit(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['selling_price' => 250]);
        $customer = $this->customer($business);
        $register = $this->openRegister($owner);

        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'credit', 'amount' => 0]], ['customer_id' => $customer->id])
            ->assertCreated()
            ->assertJson(['amount_due' => '250.00']);

        $this->assertSame('credit', Sale::query()->value('payment_status'));
        $this->assertSame('issued', Invoice::query()->value('status'));
    }

    public function test_balance_due_requires_a_customer(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);
        $register = $this->openRegister($owner);

        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'credit', 'amount' => 0]])
            ->assertStatus(422)
            ->assertJson(['message' => 'A customer is required for a credit balance']);
    }

    public function test_sale_cannot_oversell_stock(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 1]);
        $register = $this->openRegister($owner);

        $this->sell($owner, $register, [[$product, 2]], [['payment_method' => 'cash', 'amount' => 200]])
            ->assertStatus(422);
        $this->assertSame('1.000', $product->fresh()->stock_quantity);
        $this->assertSame(0, Sale::query()->count());
    }

    public function test_closed_register_cannot_take_sales(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);
        $register = $this->openRegister($owner);
        $this->actingAs($owner)->postJson("/api/registers/{$register->id}/close", ['actual_closing_cash' => 1000])->assertOk();

        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'cash', 'amount' => 100]])
            ->assertStatus(422)
            ->assertJson(['message' => 'Cash register is not open']);
    }

    public function test_editing_a_sale_rebalances_stock_cash_and_credit(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 10, 'selling_price' => 100]);
        $customer = $this->customer($business);
        $register = $this->openRegister($owner, 0);

        $saleId = $this->sell($owner, $register, [[$product, 3]], [['payment_method' => 'cash', 'amount' => 300]])->json('sale_id');

        // Change to 5 units on credit, 100 paid in cash.
        $this->actingAs($owner)->putJson("/api/sales/{$saleId}", [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100]],
            'payments' => [['payment_method' => 'cash', 'amount' => 100]],
        ])->assertOk()->assertJson(['grand_total' => '500.00', 'amount_due' => '400.00', 'payment_status' => 'partial']);

        $this->assertSame('5.000', $product->fresh()->stock_quantity);
        $this->assertSame('100.00', $register->fresh()->cash_sales);
        $this->assertSame('400.00', $customer->fresh()->credit_balance);
        $this->assertSame(-2.0, (float) StockMovement::query()->where('reference_id', $saleId)->where('note', 'Sale edited')->value('quantity'));
    }

    public function test_card_sales_can_be_edited_too(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);
        $register = $this->openRegister($owner, 0);
        $saleId = $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'card', 'amount' => 100]])->json('sale_id');

        $this->actingAs($owner)->putJson("/api/sales/{$saleId}", [
            'items' => [['product_id' => $product->id, 'quantity' => 2, 'unit_price' => 100]],
            'payments' => [['payment_method' => 'cash', 'amount' => 200]],
        ])->assertOk();

        $this->assertSame('200.00', $register->fresh()->cash_sales);
    }

    public function test_deleting_a_sale_restores_everything_and_respects_returns(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['stock_quantity' => 10]);
        $register = $this->openRegister($owner, 500);
        $saleId = $this->sell($owner, $register, [[$product, 4]], [['payment_method' => 'cash', 'amount' => 400]])->json('sale_id');

        // One unit comes back first, refunded in cash.
        $this->actingAs($owner)->postJson("/api/sales/{$saleId}/returns", [
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'refund_method' => 'cash',
            'register_id' => $register->id,
        ])->assertCreated()->assertJson(['return_number' => 'SR-000001', 'grand_total' => '100.00']);
        $this->assertSame('7.000', $product->fresh()->stock_quantity);

        $this->actingAs($owner)->deleteJson("/api/sales/{$saleId}")->assertOk();

        // Only the 3 units still out go back: never more than was sold.
        $this->assertSame('10.000', $product->fresh()->stock_quantity);
        $register->refresh();
        $this->assertSame('0.00', $register->cash_sales);
        $this->assertSame('100.00', $register->cash_refunds);
        $this->assertSame('400.00', $register->expected_closing_cash);
        $this->assertSame('cancelled', Sale::query()->find($saleId)->status);
        $this->assertSame('cancelled', Invoice::query()->where('sale_id', $saleId)->value('status'));

        $this->actingAs($owner)->deleteJson("/api/sales/{$saleId}")->assertStatus(422)->assertJson(['message' => 'Sale is already cancelled']);
    }

    public function test_sales_on_a_closed_register_cannot_be_edited_or_deleted(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);
        $register = $this->openRegister($owner);
        $saleId = $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'cash', 'amount' => 100]])->json('sale_id');
        $this->actingAs($owner)->postJson("/api/registers/{$register->id}/close", ['actual_closing_cash' => 1100])->assertOk();

        $this->actingAs($owner)->deleteJson("/api/sales/{$saleId}")
            ->assertStatus(422)->assertJson(['message' => 'Sales from a closed register cannot be deleted']);
        $this->actingAs($owner)->putJson("/api/sales/{$saleId}", [
            'items' => [['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100]],
            'payments' => [['payment_method' => 'cash', 'amount' => 100]],
        ])->assertStatus(422)->assertJson(['message' => 'Sales from a closed register cannot be edited']);
    }

    public function test_returns_cannot_exceed_sold_quantity(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);
        $register = $this->openRegister($owner);
        $saleId = $this->sell($owner, $register, [[$product, 2]], [['payment_method' => 'cash', 'amount' => 200]])->json('sale_id');

        $this->actingAs($owner)->postJson("/api/sales/{$saleId}/returns", [
            'items' => [['product_id' => $product->id, 'quantity' => 2]], 'refund_method' => 'card',
        ])->assertCreated();

        $this->actingAs($owner)->postJson("/api/sales/{$saleId}/returns", [
            'items' => [['product_id' => $product->id, 'quantity' => 1]], 'refund_method' => 'card',
        ])->assertStatus(422);

        $this->actingAs($owner)->postJson("/api/sales/{$saleId}/returns", [
            'items' => [['product_id' => $product->id, 'quantity' => 1]], 'refund_method' => 'cash',
        ])->assertStatus(422)->assertJson(['message' => 'An open cash register is required for a cash refund.']);
    }

    public function test_register_close_records_variance(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $register = $this->openRegister($owner, 1000);

        // Opening twice returns the same open register.
        $this->assertSame($register->id, $this->openRegister($owner, 50)->id);

        $this->actingAs($owner)->postJson("/api/registers/{$register->id}/close", ['actual_closing_cash' => 990])
            ->assertOk()->assertJson(['status' => 'closed', 'variance' => '-10.00']);
        $this->assertSame(1, CashRegister::query()->count());
    }
}
