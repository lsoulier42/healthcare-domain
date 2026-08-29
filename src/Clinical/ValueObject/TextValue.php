<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

final readonly class TextValue extends ObservationValue
{
    public function __construct(public string $text)
    {
    }

    public function equals(ObservationValue $other): bool
    {
        return $other instanceof self && $this->text === $other->text;
    }
}
