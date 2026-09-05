<?php

declare(strict_types=1);

namespace Healthcare\Clinical;

use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Kernel\Exception\InvalidDomainState;

/** @internal Shared invariant for links between clinical resources. */
final class PatientConsistency
{
    private function __construct()
    {
    }

    public static function assertCompatible(PatientReference $patient, ?PatientReference $linkedPatient): void
    {
        if ($linkedPatient !== null && !$patient->equals($linkedPatient)) {
            throw new InvalidDomainState('Linked resources must refer to the same patient.');
        }
    }
}
