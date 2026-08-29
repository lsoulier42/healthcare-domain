<?php

declare(strict_types=1);

namespace Healthcare\Geographic\ValueObject;

use Healthcare\Kernel\ValueObject\AbstractStringValueObject;

final readonly class CountryCode extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }

    protected static function isValid(string $value): bool
    {
        return preg_match('/^[A-Z]{2}$/', $value) === 1;
    }
}
