<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

/**
 * CIS — Code Identifiant de Spécialité. Per the ANSM/ministry
 * glossary, an 8-digit numeric code identifying a medicinal product
 * regardless of its presentations. No checksum is documented.
 */
final readonly class Cis extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return (string) preg_replace('/[^0-9]/', '', $value);
    }

    protected static function isValid(string $value): bool
    {
        return preg_match('/^\d{8}$/', $value) === 1;
    }
}
