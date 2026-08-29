<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Stable reference to a practitioner record owned by the consuming
 * bounded context. The id is the record identifier chosen by that
 * context; the attached identity is an optional snapshot and does not
 * participate in reference equality.
 */
final readonly class PractitionerReference
{
    public function __construct(
        public string $id,
        public ?PractitionerIdentity $identity = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A practitioner reference requires a non-blank id.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->id === $other->id;
    }
}
