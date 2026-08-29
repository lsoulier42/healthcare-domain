<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Stable reference to a patient record owned by the consuming bounded
 * context. The id is the record identifier chosen by that context; the
 * attached identity is an optional snapshot and does not participate in
 * reference equality: the same record keeps the same reference even if
 * its identity representation is updated.
 */
final readonly class PatientReference
{
    public string $id;

    public function __construct(
        string $id,
        public ?PatientIdentity $identity = null,
    ) {
        $id = trim($id);

        if ($id === '') {
            throw new InvalidValueObject('A patient reference requires a non-blank id.');
        }

        $this->id = $id;
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
