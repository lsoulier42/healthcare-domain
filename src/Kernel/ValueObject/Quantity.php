<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Immutable numeric quantity (FHIR Quantity semantics): a signed
 * decimal value — zero and negative values included — with an
 * optional comparison modifier. The value is kept as its original
 * decimal string: clinical quantities require deterministic decimal
 * semantics, and represented precision ("500" vs "500.0") is
 * significant, so no float conversion is ever performed.
 *
 * Positivity is a contextual constraint (e.g. medication strengths),
 * not a property of every quantity.
 */
final readonly class Quantity
{
    public DecimalString $value;

    public ?QuantityComparator $comparator;

    public function __construct(
        string $value,
        public Unit $unit,
        ?QuantityComparator $comparator = null,
    ) {
        try {
            $decimal = new DecimalString($value);
        } catch (InvalidValueObject) {
            throw new InvalidValueObject('A quantity requires a valid decimal value and a unit.');
        }

        $this->value = $decimal;
        $this->comparator = $comparator;
    }

    public function equals(self $other): bool
    {
        return $this->value->equals($other->value)
            && $this->unit->equals($other->unit)
            && $this->comparator === $other->comparator;
    }

    public function __toString(): string
    {
        return trim(sprintf(
            '%s %s %s',
            $this->comparator->value ?? '',
            (string) $this->value,
            $this->unit->code(),
        ));
    }
}
