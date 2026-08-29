<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Immutable reference to a coding system, identified by its canonical URI
 * (or another canonical identifier). No whitelist is enforced: the package
 * must represent unknown future systems as well.
 */
final readonly class CodeSystem
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueObject('A code system requires a non-blank value.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public static function ansTreG15(): self
    {
        return new self('https://mos.esante.gouv.fr/NOS/TRE_G15-ProfessionSante/FHIR/TRE-G15-ProfessionSante');
    }

    public static function ansTreR38(): self
    {
        return new self('https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale');
    }

    public static function ansTreR40(): self
    {
        return new self('https://mos.esante.gouv.fr/NOS/TRE_R40-CompetenceExclusive/FHIR/TRE-R40-CompetenceExclusive');
    }

    public static function loinc(): self
    {
        return new self('http://loinc.org');
    }

    public static function snomedCt(): self
    {
        return new self('http://snomed.info/sct');
    }

    public static function edqm(): self
    {
        return new self('https://standardterms.edqm.eu');
    }

    public static function ucum(): self
    {
        return new self('http://unitsofmeasure.org');
    }
}
