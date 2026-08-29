<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

/**
 * Immutable ratio of two quantities, e.g. a medication strength
 * of "1 mg / mL" or "500 mg / 5 mL".
 */
final readonly class Ratio
{
    public function __construct(
        public Quantity $numerator,
        public Quantity $denominator,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->numerator->equals($other->numerator)
            && $this->denominator->equals($other->denominator);
    }

    public function __toString(): string
    {
        return sprintf('%s / %s', (string) $this->numerator, (string) $this->denominator);
    }
}
