<?php

declare(strict_types=1);

namespace Healthcare\Medication\Entity;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Cip7;
use Healthcare\Kernel\ValueObject\Cip13;
use Healthcare\Kernel\ValueObject\Ucd;

/**
 * One package presentation of a medication: identified by CIP7/CIP13.
 * The UCD (smallest dispensing unit) attaches to the presentation, per
 * the official allocation rule (one UCD per CIP).
 * Construction registers this instance with its immutable medication owner.
 * The domain does not support changing that owner, including to correct an error.
 * Removing it from that owner's collection does not transfer ownership.
 */
final class MedicationPresentation
{
    public function __construct(
        private readonly string $id,
        private readonly Medication $medication,
        private ?Cip7 $cip7 = null,
        private ?Cip13 $cip13 = null,
        private ?Ucd $ucd = null,
        private ?string $packagingDescription = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A medication presentation requires an identifier.');
        }

        $medication->addPresentation($this);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function medication(): Medication
    {
        return $this->medication;
    }

    public function cip7(): ?Cip7
    {
        return $this->cip7;
    }

    public function cip13(): ?Cip13
    {
        return $this->cip13;
    }

    public function ucd(): ?Ucd
    {
        return $this->ucd;
    }

    public function packagingDescription(): ?string
    {
        return $this->packagingDescription;
    }
}
