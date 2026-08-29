<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\ValueObject\Period;

/**
 * Immutable contextual professional relationship of a practitioner:
 * a profession, an optional organization, an optional savoir-faire list
 * and an optional validity period. This is a shared semantic value, not
 * an application record: no persistence ID, no mutable active flag, no
 * collection back-references.
 *
 * Semantically duplicate savoir-faire values are deduplicated at
 * construction, consistently with the package collection conventions.
 * Equality is order-independent over the savoir-faire list.
 */
final readonly class PractitionerRole
{
    /** @var list<SavoirFaireCode> */
    public array $savoirFaire;

    /**
     * @param list<SavoirFaireCode> $savoirFaire
     */
    public function __construct(
        public ProfessionCode $profession,
        public ?OrganizationIdentity $organization = null,
        array $savoirFaire = [],
        public ?Period $validityPeriod = null,
    ) {
        $unique = [];
        foreach ($savoirFaire as $code) {
            foreach ($unique as $existing) {
                if ($existing->equals($code)) {
                    continue 2;
                }
            }

            $unique[] = $code;
        }

        $this->savoirFaire = $unique;
    }

    public function equals(self $other): bool
    {
        if (
            !$this->profession->equals($other->profession)
            || count($this->savoirFaire) !== count($other->savoirFaire)
        ) {
            return false;
        }

        $sameOrganization = $this->organization === null
            ? $other->organization === null
            : $other->organization !== null && $this->organization->equals($other->organization);

        $sameValidity = $this->validityPeriod === null
            ? $other->validityPeriod === null
            : $other->validityPeriod !== null && $this->validityPeriod->equals($other->validityPeriod);

        if (!$sameOrganization || !$sameValidity) {
            return false;
        }

        foreach ($this->savoirFaire as $code) {
            $matched = false;
            foreach ($other->savoirFaire as $otherCode) {
                if ($code->equals($otherCode)) {
                    $matched = true;
                    break;
                }
            }
            if (!$matched) {
                return false;
            }
        }

        return true;
    }
}
