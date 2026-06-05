<?php

namespace App\Services;

final class PurchaseImeiRegistrationResult
{
    public int $created = 0;

    /** @var list<array{id: int, imei_number: string, text: string}> */
    public array $createdItems = [];

    /** @var list<string> */
    public array $failed = [];

    /** @var array{duplicates: list<string>, limit_exhausted: list<string>} */
    public array $failureReasons = [
        'duplicates' => [],
        'limit_exhausted' => [],
    ];

    public int $parsedCount = 0;

    public ?string $errorField = null;

    public ?string $errorMessage = null;

    public int $purchaseLimitRemaining = 0;

    public int $modelLimitRemaining = 0;

    public function hasValidationError(): bool
    {
        return $this->errorField !== null;
    }

    public function succeeded(): bool
    {
        return ! $this->hasValidationError() && $this->created > 0;
    }
}
