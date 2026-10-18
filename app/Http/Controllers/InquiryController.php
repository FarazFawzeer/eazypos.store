<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public "Contact EazyPOS" / "Get Started" form. New businesses are created
 * by the EazyPOS team from these requests once payment is agreed.
 */
class InquiryController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255'],
            'business' => ['nullable', 'string', 'max:255'],
            'topic' => ['nullable', 'string', 'max:80'],
            'message' => ['required', 'string', 'max:5000'],
            'website' => ['nullable', 'max:0'], // honeypot: real visitors never fill it
        ], [
            'website.max' => 'Unable to send your message.',
        ]);

        unset($data['website']);

        Inquiry::query()->create($data + ['status' => 'new', 'ip_address' => $request->ip()]);

        return response()->json(['message' => 'Thank you! Your inquiry has been received. Our team will contact you soon.'], 201);
    }
}
