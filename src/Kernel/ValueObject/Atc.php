<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Atc extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }
    protected static function isValid(string $value): bool
    {
        return preg_match('/^[A-Z]\d{2}[A-Z]{2}\d{2}$/', $value) === 1;
    }
}
