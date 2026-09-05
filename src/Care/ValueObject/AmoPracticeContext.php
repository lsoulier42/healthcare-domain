<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * AMO practice context of a practitioner required to build the PS assertion
 * of an INSi téléservice call — not a generic « medical specialty » concept.
 *
 * Every INSi operation (WS_INS1..WS_INS5) carries a PS assertion whose
 * AttributeStatement may contain:
 *
 * - `identifiantFacturation` — for establishments: the FINESS géographique
 *   number, or the SIRET when the FINESS is unknown; for other actors
 *   (liberal practitioners): the AM billing identifier. The guide
 *   recommends making this data configurable in the software;
 * - `codeSpecialite` — mandatory for physicians and physicians in training
 *   only; other professions leave it null;
 * - `secteurActivite` — the AMO activity sector;
 * - `gipProfessionCode` — only under a Pro Santé Connect authentication,
 *   when the user holds several professions; omitted when the code is
 *   empty.
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 « Guide d'intégration INSi » v05.00.01
 *   (19/12/2025), §3.2 « Renseignement des Assertions et Contextes »
 *   (identifiantFacturation, codeSpecialite — obligatoire « pour les
 *   médecins et les médecins en formation », gipProfessionCode — PSC,
 *   professions multiples, secteurActivite);
 * - exemple officiel de requête « recherche INS sans carte Vitale » avec
 *   authentification Pro Santé Connect (NameID NameQualifier="PSC",
 *   attributes `codeSpecialiteAMO` and `identifiantFacturation`).
 *
 * The codes follow the Cadre d'Interopérabilité des TLSi AMO [CI]; their
 * value tables are externally governed and therefore kept as plain strings
 * rather than frozen enums. The AMO `codeSpecialite` is distinct from the
 * ordinal specialty ([SavoirFaireCode], TRE_R38).
 */
final readonly class AmoPracticeContext
{
    /** `identifiantFacturation` — AM billing identifier, FINESS or SIRET. */
    public string $billingIdentifier;

    /** `secteurActivite` — AMO activity sector. */
    public string $sectorCode;

    /** `codeSpecialite` — required for physicians, null otherwise. */
    public ?string $specialtyCode;

    /** `gipProfessionCode` — PSC only, when the user holds several professions. */
    public ?string $gipProfessionCode;

    public function __construct(
        string $billingIdentifier,
        string $sectorCode,
        ?string $specialtyCode = null,
        ?string $gipProfessionCode = null,
    ) {
        $billingIdentifier = trim($billingIdentifier);
        $sectorCode = trim($sectorCode);
        $specialtyCode = trim((string) $specialtyCode);
        $gipProfessionCode = trim((string) $gipProfessionCode);

        if ($billingIdentifier === '' || $sectorCode === '') {
            throw new InvalidValueObject(
                'An AMO practice context requires non-blank billing identifier and sector code.'
            );
        }

        $this->billingIdentifier = $billingIdentifier;
        $this->sectorCode = $sectorCode;
        $this->specialtyCode = $specialtyCode === '' ? null : $specialtyCode;
        $this->gipProfessionCode = $gipProfessionCode === '' ? null : $gipProfessionCode;
    }

    public function equals(self $other): bool
    {
        return $this->billingIdentifier === $other->billingIdentifier
            && $this->sectorCode === $other->sectorCode
            && $this->specialtyCode === $other->specialtyCode
            && $this->gipProfessionCode === $other->gipProfessionCode;
    }
}
