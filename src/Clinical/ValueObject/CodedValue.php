<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\ValueObject\CodeableConcept;

final readonly class CodedValue extends ObservationValue
{
    public function __construct(public CodeableConcept $concept)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->concept->equals($other->concept);
    }
}
