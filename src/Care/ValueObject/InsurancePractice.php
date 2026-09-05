<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * AMO practice attributes of a practitioner required to build the PS
 * assertion of an INSi téléservice call.
 *
 * Every INSi operation (WS_INS1..WS_INS5) carries a PS assertion whose
 * AttributeStatement must be populated with, at minimum:
 *
 * - `identifiantFacturation` — for establishments: the FINESS géographique
 *   number, or the SIRET when the FINESS is unknown; for other actors
 *   (liberal practitioners): the AM billing identifier. The guide
 *   recommends making this data configurable in the software;
 * - `codeSpecialite` — mandatory for physicians and physicians in training;
 * - `secteurActivite` — the AMO activity sector.
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 « Guide d'intégration INSi » v05.00.01
 *   (19/12/2025), §3.2 « Renseignement des Assertions et Contextes »;
 * - exemple officiel de requête « recherche INS sans carte Vitale » avec
 *   authentification Pro Santé Connect (NameID NameQualifier="PSC",
 *   attributes `codeSpecialiteAMO` and `identifiantFacturation`).
 *
 * The stored codes follow the Cadre d'Interopérabilité des TLSi AMO [CI];
 * their value table is externally governed and therefore kept as plain
 * strings rather than frozen enums.
 */
final readonly class InsurancePractice
{
    /** `identifiantFacturation` — AM billing identifier, FINESS or SIRET. */
    public string $billingIdentifier;

    /** `codeSpecialite` — mandatory for physicians. */
    public string $specialtyCode;

    /** `secteurActivite` — AMO activity sector. */
    public string $sectorCode;

    public function __construct(
        string $billingIdentifier,
        string $specialtyCode,
        string $sectorCode,
    ) {
        $billingIdentifier = trim($billingIdentifier);
        $specialtyCode = trim($specialtyCode);
        $sectorCode = trim($sectorCode);

        if ($billingIdentifier === '' || $specialtyCode === '' || $sectorCode === '') {
            throw new InvalidValueObject(
                'An insurance practice requires non-blank billing identifier, specialty code and sector code.'
            );
        }

        $this->billingIdentifier = $billingIdentifier;
        $this->specialtyCode = $specialtyCode;
        $this->sectorCode = $sectorCode;
    }

    public function equals(self $other): bool
    {
        return $this->billingIdentifier === $other->billingIdentifier
            && $this->specialtyCode === $other->specialtyCode
            && $this->sectorCode === $other->sectorCode;
    }
}