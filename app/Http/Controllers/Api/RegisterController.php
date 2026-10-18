<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\RegisterService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    public function __construct(private readonly RegisterService $registers) {}

    public function store(Request $request, BusinessContext $ctx): JsonResponse
    {
        $data = $request->validate(['opening_cash' => ['required', 'numeric', 'min:0', 'max:999999999999']]);

        return response()->json($this->registers->open($ctx, (float) $data['opening_cash']), 201);
    }

    public function close(Request $request, BusinessContext $ctx, string $register): JsonResponse
    {
        $data = $request->validate(['actual_closing_cash' => ['required', 'numeric', 'min:0', 'max:999999999999']]);

        return response()->json($this->registers->close($ctx, $register, (float) $data['actual_closing_cash']));
    }
}
