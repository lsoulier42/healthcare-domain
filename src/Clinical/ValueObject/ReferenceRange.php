<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Quantity;

/**
 * Reference range for an observation (laboratory-friendly, generic).
 * At least one of low, high or text must be provided.
 */
final readonly class ReferenceRange
{
    /** @var list<CodeableConcept> */
    public array $appliesTo;

    /**
     * @param list<CodeableConcept> $appliesTo
     */
    public function __construct(
        public ?Quantity $low = null,
        public ?Quantity $high = null,
        public ?string $text = null,
        array $appliesTo = [],
    ) {
        if ($low === null && $high === null && ($text === null || trim($text) === '')) {
            throw new InvalidValueObject('A reference range requires a low bound, a high bound, or a text.');
        }

        $this->appliesTo = array_values($appliesTo);
    }

    public function equals(self $other): bool
    {
        $sameLow = $this->low === null
            ? $other->low === null
            : $other->low !== null && $this->low->equals($other->low);
        $sameHigh = $this->high === null
            ? $other->high === null
            : $other->high !== null && $this->high->equals($other->high);

        if (!$sameLow || !$sameHigh || $this->text !== $other->text) {
            return false;
        }

        if (count($this->appliesTo) !== count($other->appliesTo)) {
            return false;
        }

        foreach ($this->appliesTo as $index => $concept) {
            if (!$concept->equals($other->appliesTo[$index])) {
                return false;
            }
        }

        return true;
    }
}
