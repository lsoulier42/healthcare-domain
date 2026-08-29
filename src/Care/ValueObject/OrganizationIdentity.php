<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Finess;
use Healthcare\Kernel\ValueObject\Siren;
use Healthcare\Kernel\ValueObject\Siret;

/**
 * Stable identity of a healthcare organization, common to healthcare
 * applications. Identity semantics only: no address, contact point,
 * application type/configuration, practitioner collection or lifecycle
 * state — those belong to the consuming application.
 *
 * When both SIREN and SIRET are supplied, they must refer to the same
 * legal entity (the SIREN is derived from the SIRET and compared).
 */
final readonly class OrganizationIdentity
{
    public function __construct(
        public string $name,
        public ?Finess $finess = null,
        public ?Siren $siren = null,
        public ?Siret $siret = null,
    ) {
        if (trim($name) === '') {
            throw new InvalidValueObject('An organization identity requires a non-blank name.');
        }

        if ($siren !== null && $siret !== null && !$siren->equals($siret->siren())) {
            throw new InvalidValueObject('The SIREN and SIRET must refer to the same legal entity.');
        }
    }

    public function equals(self $other): bool
    {
        $sameFiness = $this->finess === null
            ? $other->finess === null
            : $other->finess !== null && $this->finess->equals($other->finess);

        $sameSiren = $this->siren === null
            ? $other->siren === null
            : $other->siren !== null && $this->siren->equals($other->siren);

        $sameSiret = $this->siret === null
            ? $other->siret === null
            : $other->siret !== null && $this->siret->equals($other->siret);

        return $this->name === $other->name && $sameFiness && $sameSiren && $sameSiret;
    }
}
