<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Unit of measure expressed as a UCUM code through Coding.
 * The package does not embed the UCUM catalogue; unknown units remain
 * representable by their code string.
 */
final readonly class Unit
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromUcum(string $code, ?string $display = null): self
    {
        return new self(new Coding(CodeSystem::ucum(), $code, $display));
    }

    public static function milligram(): self
    {
        return self::fromUcum('mg', 'milligram');
    }

    public static function microgram(): self
    {
        return self::fromUcum('ug', 'microgram');
    }

    public static function gram(): self
    {
        return self::fromUcum('g', 'gram');
    }

    public static function milliliter(): self
    {
        return self::fromUcum('mL', 'milliliter');
    }

    public static function liter(): self
    {
        return self::fromUcum('L', 'liter');
    }

    public static function internationalUnit(): self
    {
        return self::fromUcum('[iU]', 'international unit');
    }

    public static function internationalUnitPerMilliliter(): self
    {
        return self::fromUcum('[iU]/mL', 'international unit per milliliter');
    }

    public function equals(self $other): bool
    {
        return $this->coding->equals($other->coding);
    }

    public function sameCodeAs(self $other): bool
    {
        return $this->coding->sameCodeAs($other->coding);
    }

    public function code(): string
    {
        return $this->coding->code;
    }

    public function __toString(): string
    {
        return $this->coding->code;
    }
}
