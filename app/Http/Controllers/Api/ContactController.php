<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Supplier;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Customers and suppliers (Contacts menu).
 */
class ContactController extends Controller
{
    public function storeCustomer(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $this->customerData($request);

        $customer = Customer::query()->create($data + [
            'business_id' => $ctx->businessId(),
            'credit_balance' => '0.00',
            'is_active' => true,
        ]);

        return response()->json($customer, 201);
    }

    public function updateCustomer(Request $request, BusinessContext $ctx, string $customer): JsonResponse
    {
        $row = Customer::query()->where('business_id', $ctx->businessId())->whereKey($customer)->firstOrFail();
        $row->update($this->customerData($request));

        return response()->json($row);
    }

    public function destroyCustomer(BusinessContext $ctx, string $customer): JsonResponse
    {
        $deleted = Customer::query()->where('business_id', $ctx->businessId())->whereKey($customer)->delete();
        if (! $deleted) {
            throw new BusinessRuleException('Customer not found', 404);
        }

        return response()->json(['success' => true]);
    }

    public function storeSupplier(Request $request, BusinessContext $ctx): JsonResponse
    {
        $ctx->authorize('purchases', 'You do not have permission to manage suppliers');

        $data = $request->validate($this->supplierRules() + [
            'opening_balance' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
        ]);
        $opening = Money::decimal(Money::cents($data['opening_balance'] ?? 0));
        unset($data['opening_balance']);

        $supplier = Supplier::query()->create($this->clean($data) + [
            'business_id' => $ctx->businessId(),
            'opening_balance' => $opening,
            'current_balance' => $opening,
            'is_active' => true,
        ]);

        return response()->json($supplier, 201);
    }

    public function updateSupplier(Request $request, BusinessContext $ctx, string $supplier): JsonResponse
    {
        $ctx->authorize('purchases', 'You do not have permission to manage suppliers');

        $row = Supplier::query()->where('business_id', $ctx->businessId())->whereKey($supplier)->firstOrFail();
        $row->update($this->clean($request->validate($this->supplierRules())));

        return response()->json($row);
    }

    public function destroySupplier(BusinessContext $ctx, string $supplier): JsonResponse
    {
        $ctx->authorize('purchases', 'You do not have permission to manage suppliers');

        $deleted = DB::transaction(fn () => Supplier::query()->where('business_id', $ctx->businessId())->whereKey($supplier)->delete());
        if (! $deleted) {
            throw new BusinessRuleException('Supplier not found', 404);
        }

        return response()->json(['success' => true]);
    }

    private function customerData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'customer_type' => ['required', 'in:regular,walk_in,credit'],
            'credit_limit' => ['nullable', 'numeric', 'min:0', 'max:999999999999'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
        $data['credit_limit'] = Money::decimal(Money::cents($data['credit_limit'] ?? 0));

        return $this->clean($data);
    }

    private function supplierRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Trim text values and store blanks as NULL.
     */
    private function clean(array $data): array
    {
        return array_map(fn ($v) => is_string($v) ? (trim($v) === '' ? null : trim($v)) : $v, $data);
    }
}
