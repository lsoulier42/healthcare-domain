<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final readonly class Siret extends AbstractStringValueObject
{
    protected static function isValid(string $value): bool
    {
        return preg_match('/^\d{14}$/', $value) === 1
            && Checksum::siren(substr($value, 0, 9))
            && Checksum::luhn($value);
    }

    /**
     * The SIREN of the legal entity owning this establishment: the first
     * nine digits, already validated by the SIRET checksum.
     */
    public function siren(): Siren
    {
        return new Siren(substr($this->value, 0, 9));
    }
}
