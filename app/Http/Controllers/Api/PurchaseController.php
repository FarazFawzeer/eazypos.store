<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PurchaseService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function __construct(private readonly PurchaseService $purchases) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        return response()->json($this->purchases->create($ctx, $this->payload($request)), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $purchase): JsonResponse
    {
        return response()->json($this->purchases->update($ctx, $purchase, $this->payload($request)));
    }

    public function destroy(BusinessContext $ctx, string $purchase): JsonResponse
    {
        return response()->json($this->purchases->delete($ctx, $purchase));
    }

    public function storeReturn(Request $request, BusinessContext $ctx, string $purchase): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric'],
            'refund_method' => ['required', 'string', 'max:20'],
            'register_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json($this->purchases->processReturn($ctx, $purchase, $data), 201);
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric'],
            'items.*.unit_cost' => ['required', 'numeric'],
            'items.*.selling_price' => ['nullable', 'numeric'],
            'items.*.discount' => ['nullable', 'numeric'],
            'items.*.tax' => ['nullable', 'numeric'],
            'payment_method' => ['required', 'string', 'max:20'],
            'amount_paid' => ['required', 'numeric'],
            'discount' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'shipping_charge' => ['nullable', 'numeric'],
            'other_charge' => ['nullable', 'numeric'],
            'purchase_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'register_id' => ['nullable', 'uuid'],
        ]);
    }
}
