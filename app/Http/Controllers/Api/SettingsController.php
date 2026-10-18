<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BusinessContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Business name and currency from the Settings screen.
 */
class SettingsController extends Controller
{
    public const CURRENCIES = ['LKR', 'USD', 'GBP', 'EUR'];

    public function update(Request $request, BusinessContext $ctx): JsonResponse
    {
        $ctx->authorize('settings', 'You do not have permission to change settings');

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'currency' => ['required', 'in:'.implode(',', self::CURRENCIES)],
        ]);

        $ctx->business->update(['name' => trim($data['name']), 'currency' => $data['currency']]);

        return response()->json(['success' => true, 'name' => $ctx->business->name, 'currency' => $ctx->business->currency]);
    }
}
