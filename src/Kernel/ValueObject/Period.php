<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use DateTimeImmutable;
use Healthcare\Kernel\Exception\InvalidPeriod;

/**
 * Immutable time period. Both bounds are optional; when both exist,
 * the end must not precede the start.
 */
final readonly class Period
{
    public ?DateTimeImmutable $start;

    public ?DateTimeImmutable $end;

    public function __construct(?DateTimeImmutable $start = null, ?DateTimeImmutable $end = null)
    {
        if ($start !== null && $end !== null && $end < $start) {
            throw new InvalidPeriod('A period cannot end before it starts.');
        }

        $this->start = $start;
        $this->end = $end;
    }

    public function equals(self $other): bool
    {
        return $this->start == $other->start && $this->end == $other->end;
    }
}
