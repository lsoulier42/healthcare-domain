<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Normalizes identity trait strings to one of the INSi lexical profiles
 * (see {@see InsiTraitProfile}) at the INSi téléservice / INS datamatrix
 * boundary only. Stored identity values remain untouched — the package keeps
 * preserving the supplied semantic values (trimmed only) in
 * {@see \Healthcare\Identity\ValueObject\StrictIdentityTraits}.
 *
 * The INSi téléservice rejects inputs (and outputs) that contain lowercase
 * letters, diacritics (accents, trémas, cédilles) or ligatures (Æ, Œ); the
 * allowed character set is A-Z, space, apostrophe "'" (ASCII 39) and hyphen
 * "-" (ASCII 45).
 *
 * Characters with a defined equivalence are transliterated (é → E, ç → C,
 * œ → OE…). Characters without any defined equivalence (digits, "_", "!",
 * "/", punctuation…) are NOT silently removed: the reference documents only
 * state they must not be sent, so {@see self::normalize()} raises an error
 * rather than silently transforming an identity into another string.
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 « Guide d'intégration INSi » v05.00.01
 *   (19/12/2025), §2 « Limitations du téléservice » and §3.4.1 (EF_INS01_03,
 *   EF_INS10_01, EF_INS10_02 — formats and syntactic rules per field);
 * - ANS, « Format datamatrix INS » v2.2.20230926, §5 (IDs S3/S4).
 */
final class InsiTraitsNormalizer
{
    /**
     * Unicode characters (upper- and lowercase) mapped to their ASCII base,
     * so that diacritics and ligatures are deterministically removed without
     * any dependency on intl or locale settings.
     *
     * @var array<string, string>
     */
    private const ACCENT_MAP = [
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A',
        'à' => 'A', 'á' => 'A', 'â' => 'A', 'ã' => 'A', 'ä' => 'A', 'å' => 'A',
        'Ç' => 'C', 'ç' => 'C',
        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E',
        'è' => 'E', 'é' => 'E', 'ê' => 'E', 'ë' => 'E',
        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
        'ì' => 'I', 'í' => 'I', 'î' => 'I', 'ï' => 'I',
        'Ñ' => 'N', 'ñ' => 'N',
        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O',
        'ò' => 'O', 'ó' => 'O', 'ô' => 'O', 'õ' => 'O', 'ö' => 'O', 'ø' => 'O',
        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U',
        'ù' => 'U', 'ú' => 'U', 'û' => 'U', 'ü' => 'U',
        'Ý' => 'Y', 'ý' => 'Y', 'ÿ' => 'Y',
        'Œ' => 'OE', 'œ' => 'OE', 'Æ' => 'AE', 'æ' => 'AE',
        'Š' => 'S', 'š' => 'S', 'Ž' => 'Z', 'ž' => 'Z',
        'Ð' => 'D', 'ð' => 'D', 'Þ' => 'TH', 'þ' => 'TH',
    ];

    private function __construct()
    {
    }

    /**
     * Returns the normalized form of a value under the given profile:
     * transliteration of defined equivalents, uppercase, trimming, then a
     * strict allowed-character-set check.
     *
     * @throws InvalidValueObject when the value contains characters without a
     *                            defined equivalence (silent removal would
     *                            corrupt identity data)
     */
    public static function normalize(string $value, InsiTraitProfile $profile): string
    {
        $transliterated = strtr($value, self::ACCENT_MAP);
        $upper = strtoupper($transliterated);
        $trimmed = trim($upper);

        if (preg_match('/[^A-Z \'\-]/', $trimmed) === 1) {
            throw new InvalidValueObject(sprintf(
                'INSi %s contains characters without a defined equivalence (allowed: A-Z, space, apostrophe, hyphen).',
                $profile->value
            ));
        }

        if ($trimmed === '') {
            throw new InvalidValueObject(sprintf('INSi %s must not be blank.', $profile->value));
        }

        return $trimmed;
    }

    /**
     * Whether a value is structurally valid under a profile: allowed
     * character set, first-character rule (no space or hyphen), space and
     * apostrophe not doubled or combined, plus profile-specific rules
     * (last character for given names, no double hyphen in given names).
     */
    public static function isValid(string $value, InsiTraitProfile $profile): bool
    {
        try {
            $normalized = self::normalize($value, $profile);
        } catch (InvalidValueObject) {
            return false;
        }

        if ($normalized[0] === ' ' || $normalized[0] === '-') {
            return false;
        }

        // A space and an apostrophe cannot be doubled or combined.
        if (
            str_contains($normalized, '  ')
            || str_contains($normalized, "''")
            || str_contains($normalized, " '")
            || str_contains($normalized, "' ")
        ) {
            return false;
        }

        if ($profile === InsiTraitProfile::GIVEN_NAME) {
            if (str_contains($normalized, '--')) {
                return false;
            }

            $last = $normalized[strlen($normalized) - 1];
            if ($last === '-') {
                return false;
            }

            if ($last === "'") {
                return false;
            }
        }

        return true;
    }

    public static function normalizeBirthFamilyName(string $value): string
    {
        return self::normalize($value, InsiTraitProfile::FAMILY_NAME);
    }

    public static function normalizeGivenName(string $value): string
    {
        return self::normalize($value, InsiTraitProfile::GIVEN_NAME);
    }

    public static function normalizeDatamatrixName(string $value): string
    {
        return self::normalize($value, InsiTraitProfile::DATAMATRIX);
    }

    public static function isValidBirthFamilyName(string $value): bool
    {
        return self::isValid($value, InsiTraitProfile::FAMILY_NAME);
    }

    public static function isValidGivenName(string $value): bool
    {
        return self::isValid($value, InsiTraitProfile::GIVEN_NAME);
    }

    public static function isValidDatamatrixName(string $value): bool
    {
        return self::isValid($value, InsiTraitProfile::DATAMATRIX);
    }
}
