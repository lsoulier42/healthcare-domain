<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Rpps extends AbstractStringValueObject
{
    protected static function isValid(string $value): bool
    {
        return preg_match('/^\d{11}$/', $value) === 1;
    }
}
