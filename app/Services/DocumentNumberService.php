<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\DocumentSequence;
use Illuminate\Support\Str;

/**
 * Sequential document numbers per business (INV-000001, ...).
 * Port of public.next_document_number(); must run inside a transaction.
 */
class DocumentNumberService
{
    public const PREFIXES = [
        'invoice' => 'INV',
        'quotation' => 'QUO',
        'purchase' => 'PUR',
        'purchase_return' => 'PR',
        'sale_return' => 'SR',
        'shipment' => 'SHP',
    ];

    public function next(string $businessId, string $documentType): string
    {
        $prefix = self::PREFIXES[$documentType] ?? throw new BusinessRuleException('Invalid document type');

        DocumentSequence::query()->insertOrIgnore([
            'id' => (string) Str::uuid(),
            'business_id' => $businessId,
            'document_type' => $documentType,
            'prefix' => $prefix,
            'next_number' => 1,
        ]);

        $sequence = DocumentSequence::query()
            ->where('business_id', $businessId)
            ->where('document_type', $documentType)
            ->lockForUpdate()
            ->firstOrFail();

        $number = (int) $sequence->next_number;
        $sequence->next_number = $number + 1;
        $sequence->save();

        return $sequence->prefix.'-'.str_pad((string) $number, 6, '0', STR_PAD_LEFT);
    }
}
