<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Professional savoir-faire: an ordinal specialty (TRE_R38) or an
 * exclusive competence (TRE_R40). Wraps a generic Coding so that
 * historical, deprecated and future codes remain representable — this
 * is deliberately not a closed enum.
 */
final readonly class SavoirFaireCode
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromTreR38(string $code, ?string $display = null, ?string $version = null): self
    {
        return new self(new Coding(CodeSystem::ansTreR38(), $code, $display, $version));
    }

    public static function fromTreR40(string $code, ?string $display = null, ?string $version = null): self
    {
        return new self(new Coding(CodeSystem::ansTreR40(), $code, $display, $version));
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
