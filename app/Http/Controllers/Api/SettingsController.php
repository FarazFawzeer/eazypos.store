<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\BusinessContext;
use App\Support\Money;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Settings screen: business name, currency, tax % and receipt footer.
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
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'receipt_footer' => ['nullable', 'string', 'max:255'],
        ], [
            'tax_rate.max' => 'Tax % must be between 0 and 100',
            'tax_rate.min' => 'Tax % must be between 0 and 100',
        ]);

        $footer = trim((string) ($data['receipt_footer'] ?? ''));

        $ctx->business->update([
            'name' => trim($data['name']),
            'currency' => $data['currency'],
            'tax_rate' => Money::decimal(Money::cents($data['tax_rate'] ?? 0)),
            'receipt_footer' => $footer === '' ? null : $footer,
        ]);

        return response()->json([
            'success' => true,
            'name' => $ctx->business->name,
            'currency' => $ctx->business->currency,
            'tax_rate' => (float) $ctx->business->tax_rate,
            'receipt_footer' => $ctx->business->receipt_footer,
        ]);
    }
}
