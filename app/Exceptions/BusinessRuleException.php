<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * A business rule was broken (insufficient stock, register closed, ...).
 * Rendered as HTTP 422 with the message shown to the user, exactly like the
 * "raise exception" messages of the original Supabase functions.
 */
class BusinessRuleException extends RuntimeException
{
    public function __construct(string $message, private readonly int $status = 422)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
