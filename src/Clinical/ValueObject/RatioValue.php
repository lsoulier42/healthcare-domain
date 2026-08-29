<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\ValueObject\Ratio;

final readonly class RatioValue extends ObservationValue
{
    public function __construct(public Ratio $ratio)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->ratio->equals($other->ratio);
    }
}
