<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;

/**
 * Strict identity traits of the French INS/RNIV: the mandatory traits
 * that identify the person themselves (nom de naissance, premier prénom
 * de naissance, date de naissance, sexe administratif, code COG du lieu
 * de naissance), independently of any identifier or verification status.
 *
 * The RNIV v2.0 distinguishes the first birth given name (required for
 * identity creation) from the full birth given-name list (a trait to
 * complete later): the list may be unknown while the first name is
 * already known, hence `birthGivenNames` is nullable.
 *
 * When the list is known, the RNIV expects the first given name to be
 * consistent with the beginning of the list (e.g. "MAX" or
 * "MAX-PATRICK" are consistent with a list starting with "MAX PATRICK").
 * That coherence rule is not implemented: the full RNIV normalization
 * rules are not stable enough to codify here. The supplied semantic
 * values are preserved (trimmed only), never aggressively transformed.
 */
final readonly class StrictIdentityTraits
{
    public string $birthFamilyName;

    public string $firstBirthGivenName;

    /** @var ?non-empty-list<string> */
    public ?array $birthGivenNames;

    /**
     * @param ?list<string> $birthGivenNames the complete birth given-name
     *                                       list, or null when unknown
     */
    public function __construct(
        string $birthFamilyName,
        string $firstBirthGivenName,
        ?array $birthGivenNames,
        public Date $birthDate,
        public AdministrativeGender $gender,
        public CogCode $birthPlace,
    ) {
        $normalizedFamilyName = trim($birthFamilyName);
        if ($normalizedFamilyName === '') {
            throw new InvalidValueObject('Strict identity traits require a non-blank birth family name.');
        }

        $normalizedFirstName = trim($firstBirthGivenName);
        if ($normalizedFirstName === '') {
            throw new InvalidValueObject('Strict identity traits require a non-blank first birth given name.');
        }

        $normalizedList = null;
        if ($birthGivenNames !== null) {
            $normalizedList = [];
            foreach ($birthGivenNames as $givenName) {
                $given = trim($givenName);
                if ($given !== '') {
                    $normalizedList[] = $given;
                }
            }

            if ($normalizedList === []) {
                throw new InvalidValueObject('The birth given-name list must not be empty when provided.');
            }
        }

        $this->birthFamilyName = $normalizedFamilyName;
        $this->firstBirthGivenName = $normalizedFirstName;
        $this->birthGivenNames = $normalizedList;
    }

    public function equals(self $other): bool
    {
        return $this->birthFamilyName === $other->birthFamilyName
            && $this->firstBirthGivenName === $other->firstBirthGivenName
            && $this->birthGivenNames === $other->birthGivenNames
            && $this->birthDate->equals($other->birthDate)
            && $this->gender === $other->gender
            && $this->birthPlace->equals($other->birthPlace);
    }
}
