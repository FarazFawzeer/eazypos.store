<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function __construct(private readonly ProductService $products) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        return response()->json($this->products->save($ctx, $this->payload($request)), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $product): JsonResponse
    {
        return response()->json($this->products->save($ctx, $this->payload($request), $product));
    }

    public function quick(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:100'],
            'cost_price' => ['required', 'numeric'],
            'selling_price' => ['required', 'numeric'],
            'stock_quantity' => ['nullable', 'numeric'],
            'low_stock_alert' => ['nullable', 'numeric'],
        ]);

        return response()->json($this->products->quickAdd($ctx, $data), 201);
    }

    public function prices(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate([
            'prices' => ['required', 'array', 'max:5000'],
            'prices.*.id' => ['required', 'uuid'],
            'prices.*.selling_price' => ['required', 'numeric', 'min:0'],
        ]);

        return response()->json(['success' => true, 'updated' => $this->products->updatePrices($ctx, $data['prices'])]);
    }

    public function adjustStock(Request $request, BusinessContext $ctx, string $product): JsonResponse
    {
        $data = $request->validate([
            'adjustment_type' => ['required', 'string', 'in:add,remove'],
            'quantity' => ['required', 'numeric'],
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        return response()->json($this->products->adjustStock($ctx, $product, $data['adjustment_type'], $data['quantity'], $data['reason'] ?? null));
    }

    public function masterValue(Request $request, BusinessContext $ctx, string $kind): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:120']]);
        $row = $this->products->addMasterValue($ctx, $kind, $data['name']);

        return response()->json(['id' => $row->id, 'name' => $row->name], 201);
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:255'],
            'brand' => ['nullable', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:255'],
            'cost_price' => ['required', 'numeric'],
            'selling_price' => ['required', 'numeric'],
            'stock_quantity' => ['required', 'numeric'],
            'low_stock_alert' => ['required', 'numeric'],
        ]);
    }
}
