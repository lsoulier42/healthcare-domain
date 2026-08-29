<?php

declare(strict_types=1);

namespace Healthcare\Geographic\ValueObject;

use Healthcare\Kernel\ValueObject\AbstractStringValueObject;

/**
 * Code officiel géographique (COG) d'un lieu de naissance, tel
 * qu'exigé par le référentiel INS pour le trait « code du lieu de
 * naissance » :
 *
 * - commune de naissance : 5 caractères (2 chars du département,
 *   dont 2A/2B pour la Corse, suivis des 3 chars de la commune) ;
 * - naissance à l'étranger : 99 + code pays en 3 chiffres
 *   (ex. 99100 = Royaume-Uni) ;
 * - lieu inconnu : 99999.
 *
 * This deliberately excludes the 2-character department-only codes
 * (e.g. "75", "2A"): a department is not a birthplace.
 */
final readonly class CogCode extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper(trim($value));
    }

    protected static function isValid(string $value): bool
    {
        // Structural COG check: 5 characters — 2 chars (digits, or 2A/2B
        // for Corsica) followed by 3 digits. Covers communes (e.g. 75056),
        // foreign countries (99 + 3-digit country code, e.g. 99100) and
        // the unknown-placeholder 99999. Department prefixes are not
        // whitelisted: the COG evolves and unlisted prefixes stay
        // representable rather than rejected.
        return preg_match('/^(?:[0-9]{2}|2[AB])[0-9]{3}$/', $value) === 1;
    }
}
