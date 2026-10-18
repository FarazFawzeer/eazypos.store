<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SaleService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function __construct(private readonly SaleService $sales) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        return response()->json($this->sales->complete($ctx, $this->payload($request)), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $sale): JsonResponse
    {
        return response()->json($this->sales->update($ctx, $sale, $this->payload($request)));
    }

    public function destroy(BusinessContext $ctx, string $sale): JsonResponse
    {
        return response()->json($this->sales->delete($ctx, $sale));
    }

    public function storeReturn(Request $request, BusinessContext $ctx, string $sale): JsonResponse
    {
        $data = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric'],
            'refund_method' => ['required', 'string', 'max:20'],
            'register_id' => ['nullable', 'uuid'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        return response()->json($this->sales->processReturn($ctx, $sale, $data), 201);
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['nullable', 'uuid'],
            'register_id' => ['nullable', 'uuid'],
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.quantity' => ['required', 'numeric'],
            'items.*.unit_price' => ['required', 'numeric'],
            'items.*.discount' => ['nullable', 'numeric'],
            'items.*.tax' => ['nullable', 'numeric'],
            'payments' => ['present', 'array', 'max:20'],
            'payments.*.payment_method' => ['required', 'string', 'max:20'],
            'payments.*.amount' => ['required', 'numeric'],
            'payments.*.reference_number' => ['nullable', 'string', 'max:255'],
            'payments.*.notes' => ['nullable', 'string', 'max:1000'],
            'discount' => ['nullable', 'numeric'],
            'tax' => ['nullable', 'numeric'],
            'shipping_charge' => ['nullable', 'numeric'],
            'other_charge' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
