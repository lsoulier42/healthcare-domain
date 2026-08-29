<?php

declare(strict_types=1);

namespace Healthcare\Clinical\ValueObject;

/**
 * Abstract base for observation value variants.
 */
abstract readonly class ObservationValue
{
    abstract public function equals(self $other): bool;
}
