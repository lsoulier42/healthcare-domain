<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

final readonly class IntegerValue extends ObservationValue
{
    public function __construct(public int $value)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->value === $other->value;
    }
}
