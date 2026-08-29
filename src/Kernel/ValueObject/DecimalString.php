<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Exact decimal number kept as its original string representation.
 * Precision is meaningful: "500" and "500.0" are distinct values.
 *
 * Accepted format follows the FHIR R4 decimal pattern:
 * -?(0|[1-9][0-9]*)(\.[0-9]+)?([eE][+-]?[0-9]+)?
 *
 * Sign and zero classification is computed from the decimal string
 * itself, without any float conversion, so scientific notation such
 * as "0e3" is correctly recognised as zero.
 */
final readonly class DecimalString
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (!self::isValid($normalized)) {
            throw new InvalidValueObject(sprintf('Invalid decimal value "%s".', $value));
        }

        $this->value = $normalized;
    }

    public static function isValidValue(string $value): bool
    {
        return self::isValid(trim($value));
    }

    private static function isValid(string $value): bool
    {
        return preg_match(
            '/^-?(?:0|[1-9][0-9]*)(?:\.[0-9]+)?(?:[eE][+-]?[0-9]+)?$/',
            $value,
        ) === 1;
    }

    public function isZero(): bool
    {
        return ltrim(self::mantissaDigits($this->value), '0') === '';
    }

    public function isPositive(): bool
    {
        return !str_starts_with($this->value, '-') && !$this->isZero();
    }

    public function isNegative(): bool
    {
        return str_starts_with($this->value, '-') && !$this->isZero();
    }

    private static function mantissaDigits(string $value): string
    {
        $exponentPosition = strpos(strtolower($value), 'e');
        $mantissa = $exponentPosition === false ? $value : substr($value, 0, $exponentPosition);

        return str_replace('.', '', ltrim($mantissa, '+-'));
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
