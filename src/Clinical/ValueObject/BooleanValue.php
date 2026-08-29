<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

final readonly class BooleanValue extends ObservationValue
{
    public function __construct(public bool $value)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
