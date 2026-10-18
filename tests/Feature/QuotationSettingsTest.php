<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotations_are_saved_numbered_and_loaded_back(): void
    {
        [, $owner] = $this->makeBusiness();

        $this->actingAs($owner)->postJson('/api/quotations', ['customer_name' => 'Hotel Galle', 'amount' => 12500.5])
            ->assertCreated()->assertJson(['quotation_number' => 'QUO-000001', 'customer_name' => 'Hotel Galle', 'grand_total' => '12500.50']);
        $this->actingAs($owner)->postJson('/api/quotations', ['customer_name' => '', 'amount' => 0])
            ->assertCreated()->assertJson(['quotation_number' => 'QUO-000002', 'customer_name' => 'Walk-in Customer']);
        $this->actingAs($owner)->postJson('/api/quotations', ['amount' => -1])->assertStatus(422);

        $quotations = $this->actingAs($owner)->getJson('/api/bootstrap')->assertOk()->json('quotations');
        $this->assertCount(2, $quotations);
    }

    public function test_quotations_need_sales_permission_and_stay_in_their_business(): void
    {
        [$business, $owner] = $this->makeBusiness();
        [, $otherOwner] = $this->makeBusiness(['name' => 'Other']);
        $stock = $this->addStaff($business, ['stock' => true]);

        $this->actingAs($stock)->postJson('/api/quotations', ['amount' => 10])->assertForbidden();
        $this->actingAs($owner)->postJson('/api/quotations', ['amount' => 10])->assertCreated();
        $this->assertSame([], $this->actingAs($otherOwner)->getJson('/api/bootstrap')->json('quotations'));
    }

    public function test_settings_save_tax_and_receipt_footer(): void
    {
        [, $owner] = $this->makeBusiness();

        $this->actingAs($owner)->putJson('/api/settings', ['name' => 'Shop', 'currency' => 'LKR', 'tax_rate' => 101])->assertStatus(422);
        $this->actingAs($owner)->putJson('/api/settings', ['name' => 'Shop', 'currency' => 'LKR', 'tax_rate' => 8, 'receipt_footer' => 'Thank you, visit again!'])
            ->assertOk();

        $this->actingAs($owner)->getJson('/api/bootstrap')
            ->assertJson(['account' => ['business' => 'Shop', 'taxRate' => 8, 'receiptFooter' => 'Thank you, visit again!']]);

        // Clearing the footer goes back to the default invoice text.
        $this->actingAs($owner)->putJson('/api/settings', ['name' => 'Shop', 'currency' => 'LKR', 'tax_rate' => 0, 'receipt_footer' => ''])->assertOk();
        $this->actingAs($owner)->getJson('/api/bootstrap')->assertJson(['account' => ['receiptFooter' => '']]);
    }
}
