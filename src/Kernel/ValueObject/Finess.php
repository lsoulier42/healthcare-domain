<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Finess extends AbstractStringValueObject
{
    protected static function isValid(string $value): bool
    {
        if (preg_match('/^\d{9}$/', $value) !== 1) {
            return false;
        }

        $sum = 0;
        foreach (str_split($value) as $index => $character) {
            $sum += (int) $character * ($index % 2 === 0 ? 2 : 1);
        }

        return $sum % 10 === 0;
    }
}
