<?php

declare(strict_types=1);

namespace Healthcare\Medication\ValueObject;

use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Pharmaceutical dose form coded with EDQM Standard Terms.
 */
final readonly class PharmaceuticalFormCode
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromEdqm(string $code, ?string $display = null): self
    {
        return new self(new Coding(CodeSystem::edqm(), $code, $display));
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
