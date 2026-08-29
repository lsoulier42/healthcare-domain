<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Generic external identifier for identifiers that do not deserve a
 * dedicated class. Dedicated identifiers (Rpps, Finess, Ins, ...) keep
 * their strongly typed value objects.
 */
final readonly class Identifier
{
    public string $value;

    public function __construct(
        public CodeSystem $system,
        string $value,
    ) {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueObject('An identifier requires a non-blank value.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->system->equals($other->system) && $this->value === $other->value;
    }

    public function __toString(): string
    {
        return sprintf('%s|%s', (string) $this->system, $this->value);
    }
}
