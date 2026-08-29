<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Health profession coded with an externally governed terminology
 * (canonically ANS TRE_G15). Wraps a generic Coding so that
 * historical, deprecated and future codes remain representable.
 */
final readonly class ProfessionCode
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromTreG15(string $code, ?string $display = null, ?string $version = null): self
    {
        return new self(new Coding(CodeSystem::ansTreG15(), $code, $display, $version));
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
