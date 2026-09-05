<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\Exception\InvalidDomainState;

/**
 * Identity of a human patient, independent of any application-owned
 * patient record/aggregate. Contains only identity semantics: no
 * ownership, clinical history, or persistence rules.
 *
 * RNIV status invariants are enforced at construction:
 *
 * - PROVISIONAL / VALIDATED: no INS identifier;
 * - RECOVERED / QUALIFIED:   an INS identifier is required;
 * - an identity carrying the « douteux » or « fictif » attribute
 *   ([EXI ID 26]) is forcibly held at the PROVISIONAL status — the
 *   validated()/recovered()/qualified() factories reject such attributes,
 *   and its INS identifier is invalidated (PROVISIONAL carries none);
 * - attribute combinability follows [EXI ID 24] (only
 *   « douteux » + « homonyme »), enforced by IdentityAttributes itself.
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
        public IdentityAttributes $attributes,
    ) {
        if ($attributes->requiresProvisionalStatus() && $status !== IdentityStatus::PROVISIONAL) {
            throw new InvalidDomainState(
                'An identity carrying the « douteux » or « fictif » attribute must be held '
                . 'at the PROVISIONAL status ([EXI ID 26]).'
            );
        }
    }

    public static function provisional(
        StrictIdentityTraits $traits,
        ?IdentityAttributes $attributes = null,
    ): self {
        return new self($traits, null, IdentityStatus::PROVISIONAL, $attributes ?? IdentityAttributes::empty());
    }

    public static function validated(
        StrictIdentityTraits $traits,
        ?IdentityAttributes $attributes = null,
    ): self {
        return new self($traits, null, IdentityStatus::VALIDATED, $attributes ?? IdentityAttributes::empty());
    }

    public static function recovered(
        StrictIdentityTraits $traits,
        InsIdentifier $insIdentifier,
        ?IdentityAttributes $attributes = null,
    ): self {
        return new self($traits, $insIdentifier, IdentityStatus::RECOVERED, $attributes ?? IdentityAttributes::empty());
    }

    public static function qualified(
        StrictIdentityTraits $traits,
        InsIdentifier $insIdentifier,
        ?IdentityAttributes $attributes = null,
    ): self {
        return new self($traits, $insIdentifier, IdentityStatus::QUALIFIED, $attributes ?? IdentityAttributes::empty());
    }

    /**
     * Whether calling the INSi téléservice is forbidden for this identity
     * (« douteux » / « fictif », [EXI ID 26]).
     */
    public function blocksInsiLookup(): bool
    {
        return $this->attributes->blocksInsiLookup();
    }

    public function equals(self $other): bool
    {
        $sameIns = $this->insIdentifier === null
            ? $other->insIdentifier === null
            : $other->insIdentifier !== null && $this->insIdentifier->equals($other->insIdentifier);

        return $this->traits->equals($other->traits)
            && $sameIns
            && $this->status === $other->status
            && $this->attributes->equals($other->attributes);
    }
}
