<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\ValueObject\Quantity;

final readonly class QuantityValue extends ObservationValue
{
    public function __construct(public Quantity $quantity)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->quantity->equals($other->quantity);
    }
}
