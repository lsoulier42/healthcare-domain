<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Ucd extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return (string) preg_replace('/[^0-9]/', '', $value);
    }
    protected static function isValid(string $value): bool
    {
        return preg_match('/^\d{7}$/', $value) === 1;
    }
}
