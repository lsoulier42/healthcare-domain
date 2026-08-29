<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Observation/analyte code. Wraps a CodeableConcept so an analyte can
 * carry several codings at once (e.g. a local laboratory code plus its
 * LOINC mapping), with an optional text.
 */
final readonly class ObservationCode
{
    public function __construct(public CodeableConcept $concept)
    {
    }

    public static function fromLoinc(string $code, ?string $display = null, ?string $version = null): self
    {
        return new self(new CodeableConcept(
            [new Coding(CodeSystem::loinc(), $code, $display, $version)],
        ));
    }

    /**
     * @param list<Coding> $codings
     */
    public static function fromCodings(array $codings, ?string $text = null): self
    {
        return new self(new CodeableConcept($codings, $text));
    }

    public function equals(self $other): bool
    {
        return $this->concept->equals($other->concept);
    }

    public function __toString(): string
    {
        return (string) $this->concept;
    }
}
