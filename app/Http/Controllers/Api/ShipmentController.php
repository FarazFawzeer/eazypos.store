<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ShipmentService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShipmentController extends Controller
{
    public function __construct(private readonly ShipmentService $shipments) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        return response()->json($this->shipments->create($ctx, $this->payload($request)), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $shipment): JsonResponse
    {
        return response()->json($this->shipments->update($ctx, $shipment, $this->payload($request)));
    }

    public function status(Request $request, BusinessContext $ctx, string $shipment): JsonResponse
    {
        $data = $request->validate(['status' => ['required', 'string', 'max:20']]);

        return response()->json($this->shipments->changeStatus($ctx, $shipment, $data['status']));
    }

    public function link(Request $request, BusinessContext $ctx, string $shipment): JsonResponse
    {
        $data = $request->validate(['sale_id' => ['required', 'uuid']]);

        return response()->json($this->shipments->linkSale($ctx, $shipment, $data['sale_id']));
    }

    public function destroy(BusinessContext $ctx, string $shipment): JsonResponse
    {
        return response()->json(['deleted' => $this->shipments->delete($ctx, $shipment)]);
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'sale_id' => ['nullable', 'uuid'],
            'customer_id' => ['nullable', 'uuid'],
            'shipping_date' => ['nullable', 'date'],
            'expected_delivery_date' => ['nullable', 'date'],
            'shipping_address' => ['nullable', 'string', 'max:2000'],
            'carrier' => ['nullable', 'string', 'max:255'],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipping_cost' => ['nullable', 'numeric', 'max:999999999999'],
            'status' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
