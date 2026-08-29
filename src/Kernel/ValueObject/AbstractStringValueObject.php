<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidIdentifier;

abstract readonly class AbstractStringValueObject
{
    final public function __construct(string $value)
    {
        $normalized = static::normalize($value);
        if (!static::isValid($normalized)) {
            throw new InvalidIdentifier(sprintf('Invalid %s value.', static::class));
        }

        $this->value = $normalized;
    }

    public string $value;

    final public static function isValidValue(string $value): bool
    {
        return static::isValid(static::normalize($value));
    }

    public static function tryFrom(string $value): ?static
    {
        return static::isValidValue($value) ? new static($value) : null;
    }

    public function equals(self $other): bool
    {
        return static::class === $other::class && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    protected static function normalize(string $value): string
    {
        return trim($value);
    }

    protected static function isValid(string $value): bool
    {
        return $value !== '';
    }
}
