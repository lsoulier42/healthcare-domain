<?php

declare(strict_types=1);

namespace Healthcare\Imaging\ValueObject;

use Healthcare\Kernel\Exception\InvalidIdentifier;

/**
 * Imaging accession number. Semantics differ from laboratory accession
 * identifiers, so the two are deliberately not the same type.
 */
final readonly class AccessionNumber
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidIdentifier('An accession number requires a non-blank value.');
        }

        $this->value = $normalized;
    }

    public static function tryFrom(string $value): ?self
    {
        return trim($value) === '' ? null : new self($value);
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
