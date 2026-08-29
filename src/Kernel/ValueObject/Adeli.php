<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Adeli extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9A-Z]/i', '', $value));
    }

    protected static function isValid(string $value): bool
    {
        return preg_match('/^[0-9A-Z]{9}$/', $value) === 1;
    }
}
