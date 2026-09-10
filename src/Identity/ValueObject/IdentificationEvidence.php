<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Evidence used to create or verify an identity: the type of identity
 * document or electronic identification device and its degree of confidence.
 *
 * Per the RNIV, only high-confidence devices (or their equivalent) authorize
 * the attribution of the « identité validée » or « identité qualifiée »
 * status. The high-confidence reference list comprises:
 *
 * - French users (adult or minor): CNI or passport;
 * - EU/EEA/CH/GB etc. users: passport, residence permit or national identity
 *   card;
 * - other foreign users: passport or residence permit;
 * - electronic identification devices providing a « substantiel » or
 *   « élevé » assurance level under eIDAS (e-carte d'identité, Identité
 *   Numérique La Poste, Appli carte Vitale…);
 * - the « tiers de confiance » mention (contractual conditions);
 * - the « Appli carte Vitale » mention for identities recovered in the
 *   qualified status.
 *
 * The identity-verification device list must stay user-configurable
 * (structures parameterize it and its confidence levels), and no device may
 * be selected by default. The type is therefore an open string; the named
 * factories below cover the normative high-confidence items.
 *
 * Factual references:
 * - Guide d'implémentation INS dans les logiciels v3.0 (DNS, 12/2024),
 *   exigence [EXI ID 19] et liste des justificatifs à haut niveau de
 *   confiance (cf. RNIV §3.3.2) ;
 * - RNIV, ex. EXI SI 10 / EXI SI 23 (enregistrement du type de dispositif ;
 *   pas de sélection par défaut).
 */
final readonly class IdentificationEvidence
{
    public const CNI = 'cni';

    public const PASSPORT = 'passport';

    public const RESIDENCE_PERMIT = 'residence_permit';

    public const EIDAS = 'eidas';

    public const TRUSTED_THIRD_PARTY = 'trusted_third_party';

    public const APPLI_CARTE_VITALE = 'appli_carte_vitale';

    public string $type;

    private function __construct(
        string $type,
        public bool $highConfidence,
    ) {
        $type = trim($type);

        if ($type === '') {
            throw new InvalidValueObject('An identification evidence requires a non-blank type.');
        }

        $this->type = $type;
    }

    public static function fromType(string $type, bool $highConfidence): self
    {
        return new self($type, $highConfidence);
    }

    public static function nationalIdentityCard(): self
    {
        return new self(self::CNI, true);
    }

    public static function passport(): self
    {
        return new self(self::PASSPORT, true);
    }

    public static function residencePermit(): self
    {
        return new self(self::RESIDENCE_PERMIT, true);
    }

    public static function electronicIdentification(): self
    {
        return new self(self::EIDAS, true);
    }

    public static function trustedThirdParty(): self
    {
        return new self(self::TRUSTED_THIRD_PARTY, true);
    }

    public static function appliCarteVitale(): self
    {
        return new self(self::APPLI_CARTE_VITALE, true);
    }

    public function isHighConfidence(): bool
    {
        return $this->highConfidence;
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->highConfidence === $other->highConfidence;
    }
}
