<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\StaffService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function __construct(private readonly StaffService $staff) {}

    public function invite(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:32'],
            'email' => ['required', 'email', 'max:255'],
            'role' => ['required', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
        ]);

        return response()->json($this->staff->invite($ctx, $data), 201);
    }

    public function update(Request $request, BusinessContext $ctx, string $member): JsonResponse
    {
        $data = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'username' => ['nullable', 'string', 'max:32'],
            'role' => ['required', 'string', 'max:20'],
            'permissions' => ['nullable', 'array'],
        ]);

        return response()->json($this->staff->update($ctx, $member, $data));
    }

    public function destroy(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['nullable', 'uuid'],
            'email' => ['nullable', 'email', 'max:255'],
        ]);

        return response()->json($this->staff->remove($ctx, $data['user_id'] ?? null, $data['email'] ?? null));
    }
}
