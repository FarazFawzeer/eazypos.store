<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Quotation;
use App\Services\DocumentNumberService;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Quotations screen: a customer name and an amount, saved with the next
 * QUO-000001 style number.
 */
class QuotationController extends Controller
{
    public function store(Request $request, BusinessContext $ctx, DocumentNumberService $numbers): JsonResponse
    {
        $ctx->authorize('sales', 'You do not have permission to create quotations');

        $data = $request->validate([
            'customer_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0', 'max:999999999999'],
        ], [
            'amount.required' => 'Enter the quotation amount',
            'amount.min' => 'Quotation amounts cannot be negative',
        ]);

        $amount = Money::decimal(Money::cents($data['amount']));
        $customer = trim((string) ($data['customer_name'] ?? '')) ?: 'Walk-in Customer';

        $quotation = DB::transaction(fn () => Quotation::query()->create([
            'business_id' => $ctx->businessId(),
            'customer_name' => $customer,
            'quotation_number' => $numbers->next($ctx->businessId(), 'quotation'),
            'quotation_date' => now(),
            'subtotal' => $amount,
            'grand_total' => $amount,
            'status' => 'draft',
            'created_by' => $ctx->userId(),
        ]));

        return response()->json($quotation, 201);
    }
}
