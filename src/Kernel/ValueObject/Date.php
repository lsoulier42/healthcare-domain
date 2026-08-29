<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Calendar date (ISO 8601 YYYY-MM-DD), without time, timezone or
 * offset: a date is a civil concept, not an instant in time.
 *
 * The value is strictly validated as a real calendar date (the
 * "YYYY-MM-DD" format alone would accept 2023-02-31). Useful for
 * administrative dates, document dates, birth dates, specimen dates
 * when no time is known, etc.
 *
 * No conversion to DateTimeImmutable is provided on purpose: such a
 * conversion would implicitly assign a timezone, which the civil date
 * deliberately does not carry. Consumers that need an instant must
 * pick the timezone and semantics explicitly.
 */
final readonly class Date
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (!self::isValid($normalized)) {
            throw new InvalidValueObject(sprintf('Invalid date value "%s".', $value));
        }

        $this->value = $normalized;
    }

    public static function isValidValue(string $value): bool
    {
        return self::isValid(trim($value));
    }

    public static function tryFrom(string $value): ?self
    {
        return self::isValidValue($value) ? new self($value) : null;
    }

    private static function isValid(string $value): bool
    {
        if (preg_match('/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/', $value) !== 1) {
            return false;
        }

        [$year, $month, $day] = array_map('intval', explode('-', $value));

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        return checkdate($month, $day, $year);
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
