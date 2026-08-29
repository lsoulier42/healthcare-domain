<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Complete INS identifier: an INS matricule paired with the authority
 * that assigns/interprets it. A matricule never travels alone.
 *
 * This object is pure domain semantics: no INSi request metadata, no
 * retrieval timestamp, no source practitioner, no verification trace.
 */
final readonly class InsIdentifier
{
    public function __construct(
        public InsMatricule $matricule,
        public InsAssigningAuthority $authority,
    ) {
    }

    public function equals(self $other): bool
    {
        return $this->matricule->equals($other->matricule)
            && $this->authority->equals($other->authority);
    }
}
