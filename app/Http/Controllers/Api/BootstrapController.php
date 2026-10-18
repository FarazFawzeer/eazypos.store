<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BootstrapService;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;

class BootstrapController extends Controller
{
    public function __invoke(BusinessContext $ctx, BootstrapService $bootstrap): JsonResponse
    {
        return response()->json($bootstrap->load($ctx));
    }
}
