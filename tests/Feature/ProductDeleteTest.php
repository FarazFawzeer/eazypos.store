<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_unused_product_is_deleted(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business);

        $this->actingAs($owner)->deleteJson("/api/products/{$product->id}")
            ->assertOk()->assertJson(['deleted' => true, 'archived' => false]);
        $this->assertNull(Product::query()->find($product->id));
    }

    public function test_sold_product_is_archived_and_old_invoices_keep_its_name(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $product = $this->product($business, ['name' => 'Blue Lagoon', 'sku' => 'SKU-87632']);
        $register = $this->openRegister($owner);
        $this->sell($owner, $register, [[$product, 1]], [['payment_method' => 'cash', 'amount' => 100]])->assertCreated();

        $this->actingAs($owner)->deleteJson("/api/products/{$product->id}")
            ->assertOk()->assertJson(['deleted' => false, 'archived' => true]);

        $product->refresh();
        $this->assertFalse($product->is_active);
        $this->assertNull($product->sku, 'SKU is freed for reuse');

        $boot = $this->actingAs($owner)->getJson('/api/bootstrap')->assertOk();
        $this->assertSame([], $boot->json('products'));
        $this->assertSame('Blue Lagoon', $boot->json('archived_products.0.name'));

        // The freed SKU can be used again.
        $this->actingAs($owner)->postJson('/api/products', [
            'name' => 'Blue Lagoon (new)', 'sku' => 'SKU-87632', 'category' => 'Mojito', 'unit' => 'Pieces',
            'cost_price' => 300, 'selling_price' => 450, 'stock_quantity' => 10, 'low_stock_alert' => 2,
        ])->assertCreated();
    }

    public function test_cashiers_and_other_businesses_cannot_delete_products(): void
    {
        [$business] = $this->makeBusiness();
        [, $otherOwner] = $this->makeBusiness(['name' => 'Other']);
        $cashier = $this->addStaff($business, ['sales' => true]);
        $product = $this->product($business);

        $this->actingAs($cashier)->deleteJson("/api/products/{$product->id}")->assertForbidden();
        $this->actingAs($otherOwner)->deleteJson("/api/products/{$product->id}")->assertStatus(422);
        $this->assertNotNull(Product::query()->find($product->id));
    }
}
