<?php

declare(strict_types=1);

namespace Healthcare\Kernel\Exception;

/**
 * An entity's domain invariant was violated by a state transition.
 */
class InvalidDomainState extends HealthcareDomainException
{
}
