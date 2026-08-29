<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Siren extends AbstractStringValueObject
{
    protected static function isValid(string $value): bool
    {
        return Checksum::siren($value);
    }
}
