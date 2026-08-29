<?php

declare(strict_types=1);

namespace Healthcare\Medication\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\Ratio;

/**
 * Strength of a medication component: either a simple quantity
 * (e.g. 500 mg) or a concentration ratio (e.g. 1 mg / mL).
 *
 * A medication strength is an exact declared value: quantities with a
 * comparison modifier (e.g. "< 500 mg") are rejected, and every value
 * must be strictly positive. Both constraints are contextual and
 * enforced here, not on the generic Quantity.
 */
final readonly class MedicationStrength
{
    public function __construct(public Quantity|Ratio $value)
    {
        if ($value instanceof Quantity) {
            self::assertExactPositive($value, 'A medication strength quantity');
        } else {
            self::assertExactPositive($value->numerator, 'A medication strength ratio numerator');
            self::assertExactPositive($value->denominator, 'A medication strength ratio denominator');
        }
    }

    private static function assertExactPositive(Quantity $quantity, string $label): void
    {
        if ($quantity->comparator !== null) {
            throw new InvalidValueObject(sprintf('%s cannot carry a comparison modifier.', $label));
        }

        if (!$quantity->value->isPositive()) {
            throw new InvalidValueObject(sprintf('%s must be strictly positive.', $label));
        }
    }

    public function equals(self $other): bool
    {
        if ($this->value::class !== $other->value::class) {
            return false;
        }

        return $this->value->equals($other->value);
    }

    public function __toString(): string
    {
        return (string) $this->value;
    }
}
