<?php

namespace Database\Seeders;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local demo data only. Never run in production: real businesses are
 * created from the Team Console or imported from Supabase.
 *
 * Demo logins (local only):
 *   Team console: admin@eazypos.test / DemoAdmin#2026
 *   Shop owner:   owner@eazypos.test / DemoOwner#2026
 *   Cashier:      cashier@eazypos.test / DemoCashier#2026
 */
class DatabaseSeeder extends Seeder
{
    public const DEMO_PASSWORDS = [
        'admin@eazypos.test' => 'DemoAdmin#2026',
        'owner@eazypos.test' => 'DemoOwner#2026',
        'cashier@eazypos.test' => 'DemoCashier#2026',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->error('Demo data is not seeded in production.');

            return;
        }

        $admin = User::query()->firstOrCreate(['email' => 'admin@eazypos.test'], ['name' => 'EazyPOS Team', 'password' => self::DEMO_PASSWORDS['admin@eazypos.test']]);
        $admin->forceFill(['is_super_admin' => true, 'email_verified_at' => now()])->save();

        $owner = User::query()->firstOrCreate(['email' => 'owner@eazypos.test'], ['name' => 'Demo Owner', 'password' => self::DEMO_PASSWORDS['owner@eazypos.test']]);
        $cashier = User::query()->firstOrCreate(['email' => 'cashier@eazypos.test'], ['name' => 'Demo Cashier', 'password' => self::DEMO_PASSWORDS['cashier@eazypos.test']]);

        $business = Business::query()->firstOrCreate(['name' => 'Demo Grocery'], [
            'business_type' => 'Grocery',
            'country' => 'Sri Lanka',
            'currency' => 'LKR',
            'email' => 'owner@eazypos.test',
            'phone' => '+94771234567',
            'address' => '12 Main Street, Colombo',
            'status' => 'active',
            'owner_id' => $owner->id,
        ]);

        BusinessUser::query()->firstOrCreate(['business_id' => $business->id, 'user_id' => $owner->id], [
            'role' => 'owner', 'permissions' => BusinessUser::OWNER_PERMISSIONS, 'status' => 'active', 'full_name' => 'Demo Owner', 'username' => 'demoowner',
        ]);
        BusinessUser::query()->firstOrCreate(['business_id' => $business->id, 'user_id' => $cashier->id], [
            'role' => 'cashier',
            'permissions' => BusinessUser::cleanPermissions(['sales' => true]),
            'status' => 'active',
            'full_name' => 'Demo Cashier',
            'username' => 'democashier',
        ]);

        $categories = collect(['Groceries', 'Beverages', 'Household'])
            ->mapWithKeys(fn ($n) => [$n => Category::query()->firstOrCreate(['business_id' => $business->id, 'name' => $n])]);
        $units = collect(['Pieces', 'Kilograms', 'Litres'])
            ->mapWithKeys(fn ($n) => [$n => Unit::query()->firstOrCreate(['business_id' => $business->id, 'name' => $n])]);

        $products = [
            ['Basmati Rice 1kg', 'SKU-10001', 'Groceries', 'Kilograms', 380, 450, 40],
            ['Sugar 1kg', 'SKU-10002', 'Groceries', 'Kilograms', 230, 270, 25],
            ['Milk Powder 400g', 'SKU-10003', 'Groceries', 'Pieces', 1050, 1190, 12],
            ['Orange Juice 1L', 'SKU-10004', 'Beverages', 'Litres', 520, 640, 18],
            ['Mineral Water 1.5L', 'SKU-10005', 'Beverages', 'Pieces', 90, 130, 60],
            ['Dish Wash Liquid', 'SKU-10006', 'Household', 'Pieces', 310, 395, 4],
        ];
        foreach ($products as [$name, $sku, $category, $unit, $cost, $sell, $stock]) {
            $product = Product::query()->firstOrCreate(['business_id' => $business->id, 'sku' => $sku], [
                'name' => $name, 'barcode' => $sku, 'category_id' => $categories[$category]->id, 'unit_id' => $units[$unit]->id,
                'cost_price' => $cost, 'selling_price' => $sell, 'stock_quantity' => $stock, 'low_stock_alert' => 5, 'is_active' => true,
            ]);
            if ($product->wasRecentlyCreated) {
                StockMovement::query()->create(['business_id' => $business->id, 'product_id' => $product->id, 'movement_type' => 'opening_stock', 'quantity' => $stock, 'note' => 'Opening stock', 'created_by' => $owner->id]);
            }
        }

        Customer::query()->firstOrCreate(['business_id' => $business->id, 'name' => 'Nimal Perera'], ['phone' => '0771112223', 'customer_type' => 'credit', 'credit_limit' => 25000]);
        Customer::query()->firstOrCreate(['business_id' => $business->id, 'name' => 'Fathima Rizwan'], ['phone' => '0774445556', 'customer_type' => 'regular']);
        Supplier::query()->firstOrCreate(['business_id' => $business->id, 'name' => 'Lanka Wholesale Traders'], ['contact_person' => 'Kamal', 'phone' => '0112223344']);

        foreach (['Salary', 'Transport', 'Utilities', 'Other'] as $name) {
            ExpenseCategory::query()->firstOrCreate(['business_id' => $business->id, 'name' => $name]);
        }
    }
}
