<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Identity of a human patient, independent of any application-owned
 * patient record/aggregate. Contains only identity semantics: no
 * ownership, clinical history, or persistence rules.
 *
 * RNIV status invariants are enforced at construction:
 *
 * - PROVISIONAL / VALIDATED: no INS identifier;
 * - RECOVERED / QUALIFIED:   an INS identifier is required.
 *
 * Use the named factories; the constructor is private so incoherent
 * RNIV states cannot be built. The object is an immutable value: a
 * state change means constructing a new value.
 */
final readonly class PatientIdentity
{
    private function __construct(
        public StrictIdentityTraits $traits,
        public ?InsIdentifier $insIdentifier,
        public IdentityStatus $status,
    ) {
    }

    public static function provisional(StrictIdentityTraits $traits): self
    {
        return new self($traits, null, IdentityStatus::PROVISIONAL);
    }

    public static function validated(StrictIdentityTraits $traits): self
    {
        return new self($traits, null, IdentityStatus::VALIDATED);
    }

    public static function recovered(StrictIdentityTraits $traits, InsIdentifier $insIdentifier): self
    {
        return new self($traits, $insIdentifier, IdentityStatus::RECOVERED);
    }

    public static function qualified(StrictIdentityTraits $traits, InsIdentifier $insIdentifier): self
    {
        return new self($traits, $insIdentifier, IdentityStatus::QUALIFIED);
    }

    public function equals(self $other): bool
    {
        $sameIns = $this->insIdentifier === null
            ? $other->insIdentifier === null
            : $other->insIdentifier !== null && $this->insIdentifier->equals($other->insIdentifier);

        return $this->traits->equals($other->traits)
            && $sameIns
            && $this->status === $other->status;
    }
}
