<?php

namespace App\Exceptions;

use RuntimeException;

class InsufficientWalletBalanceException extends RuntimeException
{
    public function __construct(
        public readonly float $balance,
        public readonly float $required,
        string $message = 'Insufficient wallet balance.',
    ) {
        parent::__construct($message);
    }
}
