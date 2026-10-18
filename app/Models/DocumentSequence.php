<?php

namespace App\Models;

class DocumentSequence extends BaseModel
{
    protected $table = 'document_sequences';

    protected function casts(): array
    {
        return [
            'next_number' => 'integer',
        ];
    }
}
