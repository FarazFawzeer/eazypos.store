<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExpenseService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(private readonly ExpenseService $expenses) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        return response()->json($this->expenses->create($ctx, $this->payload($request)), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $expense): JsonResponse
    {
        return response()->json($this->expenses->update($ctx, $expense, $this->payload($request)));
    }

    public function destroy(BusinessContext $ctx, string $expense): JsonResponse
    {
        return response()->json($this->expenses->delete($ctx, $expense));
    }

    public function storeCategory(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        return response()->json($this->expenses->createCategory($ctx, $data['name']), 201);
    }

    public function updateCategory(Request $request, BusinessContext $ctx, string $category): JsonResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:80']]);

        return response()->json($this->expenses->updateCategory($ctx, $category, $data['name']));
    }

    public function destroyCategory(BusinessContext $ctx, string $category): JsonResponse
    {
        return response()->json($this->expenses->deleteCategory($ctx, $category));
    }

    private function payload(Request $request): array
    {
        return $request->validate([
            'category_id' => ['nullable', 'uuid'],
            'description' => ['required', 'string', 'max:500'],
            'amount' => ['required', 'numeric'],
            'payment_method' => ['required', 'string', 'max:20'],
            'expense_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'register_id' => ['nullable', 'uuid'],
        ]);
    }
}
