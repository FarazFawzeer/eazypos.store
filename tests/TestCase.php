<?php

namespace Tests;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\CashRegister;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function makeBusiness(array $overrides = []): array
    {
        $owner = User::factory()->create();
        $business = Business::query()->create($overrides + [
            'name' => 'Test Shop',
            'country' => 'Sri Lanka',
            'currency' => 'LKR',
            'status' => 'active',
            'owner_id' => $owner->id,
        ]);
        $member = BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'permissions' => BusinessUser::OWNER_PERMISSIONS,
            'status' => 'active',
            'full_name' => 'Owner',
        ]);

        return [$business, $owner, $member];
    }

    protected function addStaff(Business $business, array $permissions, string $role = 'cashier'): User
    {
        $user = User::factory()->create();
        BusinessUser::query()->create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => $role,
            'permissions' => BusinessUser::cleanPermissions($permissions),
            'status' => 'active',
            'full_name' => 'Staff',
        ]);

        return $user;
    }

    protected function product(Business $business, array $overrides = []): Product
    {
        return Product::query()->create($overrides + [
            'business_id' => $business->id,
            'name' => 'Product '.uniqid(),
            'sku' => 'SKU-'.uniqid(),
            'cost_price' => 60,
            'selling_price' => 100,
            'stock_quantity' => 10,
            'low_stock_alert' => 2,
            'is_active' => true,
        ]);
    }

    protected function customer(Business $business): Customer
    {
        return Customer::query()->create(['business_id' => $business->id, 'name' => 'Customer '.uniqid()]);
    }

    protected function supplier(Business $business): Supplier
    {
        return Supplier::query()->create(['business_id' => $business->id, 'name' => 'Supplier '.uniqid()]);
    }

    protected function openRegister(User $user, float $cash = 1000): CashRegister
    {
        $response = $this->actingAs($user)->postJson('/api/registers', ['opening_cash' => $cash])->assertCreated();

        return CashRegister::query()->findOrFail($response->json('id'));
    }

    protected function sell(User $user, CashRegister $register, array $lines, array $payments, array $extra = []): TestResponse
    {
        return $this->actingAs($user)->postJson('/api/sales', $extra + [
            'register_id' => $register->id,
            'items' => array_map(fn ($l) => ['product_id' => $l[0]->id, 'quantity' => $l[1], 'unit_price' => $l[2] ?? (float) $l[0]->selling_price, 'discount' => 0, 'tax' => 0], $lines),
            'payments' => $payments,
            'discount' => 0,
            'tax' => 0,
            'shipping_charge' => 0,
            'other_charge' => 0,
        ]);
    }
}
