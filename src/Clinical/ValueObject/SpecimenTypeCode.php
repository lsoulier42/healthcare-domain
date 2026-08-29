<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Specimen type code. SNOMED CT is the canonical system for specimen
 * types, but any coding system may be used.
 */
final readonly class SpecimenTypeCode
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromSnomedCt(string $code, ?string $display = null, ?string $version = null): self
    {
        return new self(new Coding(CodeSystem::snomedCt(), $code, $display, $version));
    }

    public function equals(self $other): bool
    {
        return $this->coding->equals($other->coding);
    }

    public function sameCodeAs(self $other): bool
    {
        return $this->coding->sameCodeAs($other->coding);
    }

    public function __toString(): string
    {
        return (string) $this->coding;
    }
}
