<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\BusinessUser;
use App\Models\CashRegister;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\PurchaseReturn;
use App\Models\PurchaseReturnItem;
use App\Models\Quotation;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\SaleReturn;
use App\Models\SaleReturnItem;
use App\Models\Shipment;
use App\Models\StaffInvitation;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Support\BusinessContext;

/**
 * Everything the POS screen loads at start-up and after each change, in the
 * same row shape the original app read from Supabase. Visibility follows the
 * original row-level security rules.
 */
class BootstrapService
{
    public function load(BusinessContext $ctx): array
    {
        $id = $ctx->businessId();
        $business = $ctx->business;
        $member = $ctx->membership;

        $scoped = fn (string $model) => $model::query()->where('business_id', $id);

        $members = $scoped(BusinessUser::class)
            ->select(['id', 'user_id', 'role', 'permissions', 'status', 'full_name', 'username', 'created_at'])
            ->when(! $ctx->isAdminOrOwner(), fn ($q) => $q->where('user_id', $ctx->userId()))
            ->orderBy('created_at')
            ->get();

        return [
            'account' => [
                'userId' => $ctx->userId(),
                'email' => $ctx->user->email,
                'businessId' => $id,
                'business' => $business->name,
                'owner' => $member->full_name ?: $ctx->user->name ?: $ctx->user->email,
                'username' => $member->username ?? '',
                'role' => $member->role,
                'permissions' => $member->permissions ?? [],
                'currency' => $business->currency ?: 'LKR',
                'phone' => $business->phone ?? '',
                'address' => $business->address ?? '',
                'logoUrl' => $business->logo_url ?? '',
                'taxRate' => (float) ($business->tax_rate ?? 0),
                'receiptFooter' => $business->receipt_footer ?? '',
            ],
            'products' => $scoped(Product::class)->where('is_active', true)->orderBy('name')->get(),
            // Names of archived products, so old invoices still show what was sold.
            'archived_products' => $scoped(Product::class)->where('is_active', false)->get(['id', 'name', 'image_url']),
            'categories' => $scoped(Category::class)->orderBy('name')->get(),
            'brands' => $scoped(Brand::class)->orderBy('name')->get(),
            'units' => $scoped(Unit::class)->orderBy('name')->get(),
            'customers' => $scoped(Customer::class)->where('is_active', true)->orderBy('name')->get(),
            'suppliers' => $ctx->can('purchases') ? $scoped(Supplier::class)->where('is_active', true)->orderBy('name')->get() : [],
            'sales' => $scoped(Sale::class)->orderByDesc('sale_date')->get(),
            'sale_items' => $scoped(SaleItem::class)->orderBy('created_at')->get(),
            'sale_payments' => $scoped(SalePayment::class)->orderBy('created_at')->get(),
            'purchases' => $scoped(Purchase::class)->orderByDesc('purchase_date')->get(),
            'purchase_items' => $scoped(PurchaseItem::class)->orderBy('created_at')->get(),
            'purchase_returns' => $scoped(PurchaseReturn::class)->orderByDesc('return_date')->get(),
            'purchase_return_items' => $scoped(PurchaseReturnItem::class)->get(),
            'expenses' => $scoped(Expense::class)->orderByDesc('created_at')->get(),
            'expense_categories' => $scoped(ExpenseCategory::class)->where('is_active', true)->orderBy('name')->get(),
            'cash_registers' => $scoped(CashRegister::class)->orderByDesc('opened_at')->get(),
            'sale_returns' => $scoped(SaleReturn::class)->orderByDesc('return_date')->get(),
            'sale_return_items' => $scoped(SaleReturnItem::class)->get(),
            'business_users' => $members,
            'staff_invitations' => $ctx->isAdminOrOwner()
                ? $scoped(StaffInvitation::class)->select(['id', 'email', 'role', 'permissions', 'status', 'expires_at', 'created_at'])->orderByDesc('created_at')->get()
                : [],
            'shipments' => $ctx->can('shipments') ? $scoped(Shipment::class)->orderByDesc('created_at')->limit(100)->get() : [],
            'quotations' => $scoped(Quotation::class)->orderByDesc('quotation_date')->get(),
            'stock_movements' => $scoped(StockMovement::class)->orderByDesc('created_at')->limit(100)->get(),
        ];
    }
}
