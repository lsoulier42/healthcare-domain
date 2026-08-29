<?php

declare(strict_types=1);

namespace Healthcare\Medication\ValueObject;

use Healthcare\Medication\Entity\ActiveSubstance;

/**
 * Composition of a medication: one active substance with an optional
 * strength, expressed as a Quantity or a concentration Ratio.
 */
final readonly class MedicationComponent
{
    public function __construct(
        public ActiveSubstance $substance,
        public ?MedicationStrength $strength = null,
    ) {
    }

    public function equals(self $other): bool
    {
        $sameSubstance = $this->substance->id() === $other->substance->id();
        $sameStrength = $this->strength === null
            ? $other->strength === null
            : $other->strength !== null && $this->strength->equals($other->strength);

        return $sameSubstance && $sameStrength;
    }
}
