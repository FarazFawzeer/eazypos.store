<?php

namespace Tests\Feature;

use App\Models\BusinessUser;
use App\Models\Inquiry;
use App\Models\Product;
use App\Models\Sale;
use App\Models\StaffInvitation;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuthStaffImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_pages_render(): void
    {
        $this->get('/')->assertOk()->assertSee('Welcome back.', false)->assertDontSee('supabase');
        $this->get('/inquiry')->assertOk()->assertSee('Send us a message');
        $this->get('/login')->assertOk();
        $this->get('/app')->assertRedirect('/login');
        $this->get('/index.html')->assertRedirect('/');
    }

    public function test_owner_signs_in_with_email_or_username(): void
    {
        [$business, $owner, $member] = $this->makeBusiness();
        $member->update(['username' => 'shopowner']);

        $this->postJson('/login', ['login' => $owner->email, 'password' => 'wrong'])->assertStatus(422)->assertJson(['message' => 'Invalid login credentials']);
        $this->postJson('/login', ['login' => 'shopowner', 'password' => 'password'])->assertOk()->assertJson(['redirect' => route('app')]);
        $this->get('/app')->assertOk()->assertSee('js/pos/01-core.js', false);
    }

    public function test_sign_in_is_rate_limited(): void
    {
        [, $owner] = $this->makeBusiness();
        foreach (range(1, 5) as $_) {
            $this->postJson('/login', ['login' => $owner->email, 'password' => 'nope']);
        }
        $this->postJson('/login', ['login' => $owner->email, 'password' => 'password'])->assertStatus(429);
    }

    public function test_suspended_business_cannot_sign_in_or_keep_using_the_api(): void
    {
        [$business, $owner] = $this->makeBusiness();
        $this->actingAs($owner)->getJson('/api/bootstrap')->assertOk();

        $business->update(['status' => 'suspended']);
        $this->actingAs($owner)->getJson('/api/bootstrap')->assertStatus(401);
        $this->postJson('/login', ['login' => $owner->email, 'password' => 'password'])
            ->assertForbidden()->assertJson(['message' => 'Your business account is not active. Please contact EazyPOS support.']);
    }

    public function test_team_console_creates_businesses_for_approved_customers(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['is_super_admin' => true])->save();
        [, $owner] = $this->makeBusiness();

        $this->actingAs($owner)->get('/admin')->assertForbidden();

        $this->actingAs($admin)->get('/admin')->assertOk()->assertSee('Team Console');
        $this->actingAs($admin)->post('/admin/businesses', [
            'name' => 'Rifnaz Grocery', 'country' => 'Sri Lanka', 'currency' => 'LKR',
            'owner_name' => 'Rifnaz', 'owner_email' => 'Rifnaz@Example.com', 'owner_password' => 'Start#12345',
        ])->assertRedirect(route('admin.dashboard'));

        auth()->logout();
        $this->postJson('/login', ['login' => 'rifnaz@example.com', 'password' => 'Start#12345'])->assertOk()->assertJson(['redirect' => route('app')]);
        $this->getJson('/api/bootstrap')->assertOk()->assertJson(['account' => ['business' => 'Rifnaz Grocery', 'role' => 'owner', 'currency' => 'LKR']]);
    }

    public function test_staff_invitation_can_be_accepted_once(): void
    {
        [$business, $owner] = $this->makeBusiness();

        $invite = $this->actingAs($owner)->postJson('/api/staff/invitations', [
            'name' => 'Kasun', 'username' => 'kasun', 'email' => 'kasun@example.com', 'role' => 'cashier', 'permissions' => ['sales' => true, 'products' => true],
        ])->assertCreated()->assertJson(['email_sent' => false]);
        $token = Str::afterLast($invite->json('invite_url'), '/');
        auth()->logout();

        $this->get("/invitation/{$token}")->assertOk()->assertSee('Finish your EazyPOS account.', false);
        $this->postJson("/invitation/{$token}", ['full_name' => 'Kasun Silva', 'username' => 'kasun', 'password' => 'short', 'password_confirmation' => 'short'])
            ->assertStatus(422);
        $this->postJson("/invitation/{$token}", ['full_name' => 'Kasun Silva', 'username' => 'kasun', 'password' => 'Cashier#2026', 'password_confirmation' => 'Cashier#2026'])
            ->assertOk()->assertJson(['redirect' => route('app')]);

        $member = BusinessUser::query()->where('username', 'kasun')->firstOrFail();
        $this->assertSame('cashier', $member->role);
        $this->assertTrue($member->permissions['products']);
        $this->assertSame('accepted', StaffInvitation::query()->value('status'));

        auth()->logout();
        $this->postJson("/invitation/{$token}", ['full_name' => 'Again', 'password' => 'Cashier#2026', 'password_confirmation' => 'Cashier#2026'])->assertStatus(422);
    }

    public function test_owner_manages_staff_but_cannot_lose_the_owner_role(): void
    {
        [$business, $owner, $ownerMember] = $this->makeBusiness();
        $staff = $this->addStaff($business, ['sales' => true]);
        $staffMember = BusinessUser::query()->where('user_id', $staff->id)->first();

        $this->actingAs($owner)->putJson("/api/staff/{$staffMember->id}", ['full_name' => 'Senior', 'role' => 'manager', 'permissions' => ['sales' => true, 'stock' => true, 'bogus' => true]])
            ->assertOk()->assertJson(['role' => 'manager']);
        $this->assertArrayNotHasKey('bogus', $staffMember->fresh()->permissions);

        // The edit form has no "owner" option; the owner keeps the role anyway.
        $this->actingAs($owner)->putJson("/api/staff/{$ownerMember->id}", ['full_name' => 'Boss', 'role' => 'cashier', 'permissions' => []])
            ->assertOk()->assertJson(['role' => 'owner']);

        $this->actingAs($owner)->postJson('/api/staff/remove', ['user_id' => $owner->id])->assertStatus(422);
        $this->actingAs($owner)->postJson('/api/staff/remove', ['user_id' => $staff->id])->assertOk();
        $this->assertNull(User::query()->find($staff->id), 'login removed when it belongs to no business');
    }

    public function test_inquiry_form_is_stored_and_spam_is_rejected(): void
    {
        $this->postJson('/inquiry', ['name' => 'A', 'email' => 'a@example.com', 'message' => 'Need POS', 'topic' => 'Getting started'])->assertCreated();
        $this->postJson('/inquiry', ['name' => 'Bot', 'email' => 'b@example.com', 'message' => 'spam', 'website' => 'http://spam'])->assertStatus(422);
        $this->assertSame(1, Inquiry::query()->count());
    }

    public function test_supabase_import_keeps_ids_and_passwords(): void
    {
        $userId = (string) Str::uuid();
        $businessId = (string) Str::uuid();
        $productId = (string) Str::uuid();
        $saleId = (string) Str::uuid();
        // Supabase (Go bcrypt) writes $2a$ hashes.
        $supabaseHash = '$2a$'.substr(Hash::make('OldPass#123'), 4);

        $export = [
            'users' => [['id' => $userId, 'email' => 'Owner@Shop.lk', 'encrypted_password' => $supabaseHash, 'email_confirmed_at' => '2026-01-02 10:00:00+00', 'raw_user_meta_data' => ['owner_name' => 'Shop Owner'], 'created_at' => '2026-01-01T09:00:00.123456+00:00', 'updated_at' => null, 'last_sign_in_at' => null]],
            'businesses' => [['id' => $businessId, 'name' => 'Old Shop', 'currency' => 'LKR', 'status' => 'active', 'owner_id' => $userId, 'created_at' => '2026-01-01T09:00:00+00:00', 'updated_at' => '2026-01-01T09:00:00+00:00']],
            'business_users' => [['id' => (string) Str::uuid(), 'business_id' => $businessId, 'user_id' => $userId, 'role' => 'owner', 'permissions' => ['sales' => true], 'status' => 'active', 'full_name' => 'Shop Owner', 'username' => null, 'created_at' => '2026-01-01T09:00:00+00:00']],
            'products' => [['id' => $productId, 'business_id' => $businessId, 'name' => 'Soap', 'sku' => 'S1', 'cost_price' => 10, 'selling_price' => 15.5, 'stock_quantity' => 4, 'low_stock_alert' => 1, 'is_active' => true, 'created_at' => '2026-01-01T09:00:00+00:00', 'updated_at' => '2026-01-01T09:00:00+00:00']],
            'sales' => [['id' => $saleId, 'business_id' => $businessId, 'invoice_number' => 'INV-000007', 'sale_date' => '2026-02-01T08:30:00+00:00', 'payment_status' => 'due', 'grand_total' => 31, 'status' => 'completed', 'created_at' => '2026-02-01T08:30:00+00:00', 'updated_at' => '2026-02-01T08:30:00+00:00']],
            'sale_items' => [['id' => (string) Str::uuid(), 'sale_id' => $saleId, 'business_id' => $businessId, 'product_id' => $productId, 'quantity' => 2, 'unit_price' => 15.5, 'line_total' => 31, 'created_at' => '2026-02-01T08:30:00+00:00']],
            'stock_movements' => [['id' => (string) Str::uuid(), 'business_id' => $businessId, 'product_id' => $productId, 'movement_type' => 'adjustment_remove', 'quantity' => 3, 'created_at' => '2026-02-01T08:30:00+00:00']],
            'document_sequences' => [['id' => (string) Str::uuid(), 'business_id' => $businessId, 'document_type' => 'invoice', 'prefix' => 'INV', 'next_number' => 8, 'created_at' => '2026-01-01T09:00:00+00:00', 'updated_at' => '2026-01-01T09:00:00+00:00']],
        ];
        $file = storage_path('framework/testing-export.json');
        file_put_contents($file, json_encode(['eazypos_export' => $export]));

        $this->artisan('eazypos:import-supabase', ['file' => $file])->assertSuccessful();
        $this->artisan('eazypos:import-supabase', ['file' => $file])->assertSuccessful(); // re-run is safe
        unlink($file);

        $this->assertSame(1, User::query()->count());
        $this->assertSame('credit', Sale::query()->find($saleId)->payment_status);
        $this->assertSame(-3.0, (float) StockMovement::query()->value('quantity'));

        $this->postJson('/login', ['login' => 'owner@shop.lk', 'password' => 'OldPass#123'])->assertOk();
        $register = $this->openRegister(User::query()->find($userId));
        $this->sell(User::query()->find($userId), $register, [[Product::query()->find($productId), 1]], [['payment_method' => 'cash', 'amount' => 15.5]])
            ->assertCreated()->assertJson(['invoice_number' => 'INV-000008']);
    }
}
