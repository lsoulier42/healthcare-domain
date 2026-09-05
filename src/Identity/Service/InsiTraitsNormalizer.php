<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

/**
 * Normalizes identity trait strings to the exact lexical profile required by
 * the French INSi téléservice and the INS datamatrix.
 *
 * The INSi téléservice rejects inputs (and outputs) that contain lowercase
 * letters, diacritics (accents, trémas, cédilles) or ligatures (Æ, Œ). The
 * allowed character set is limited to:
 *
 * - letters A-Z (uppercase);
 * - space;
 * - apostrophe "'" (ASCII 39);
 * - single or double hyphen "-" / "--" (ASCII 45).
 *
 * Syntactic rules enforced by {@see isValid()}:
 *
 * - the first character must not be a space or a hyphen (it may be an
 *   apostrophe);
 * - a space and an apostrophe cannot be doubled or combined.
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 « Guide d'intégration INSi » v05.00.01
 *   (19/12/2025), §2 « Limitations du téléservice » (no lowercase, diacritic
 *   or ligature) and §3.4.1 (EF_INS01_03, EF_INS10_01, EF_INS10_02 : formats
 *   A-Z / space / apostrophe / tiret / double tiret, first-character rules);
 * - ANS, « Format datamatrix INS » v2.2.20230926, §5 (IDs S3 and S4: uppercase
 *   without accent or diacritic, hyphen and apostrophe allowed).
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
     * Returns the normalized form of a trait string: uppercase, accent- and
     * ligature-free, restricted to the INSi allowed character set, trimmed.
     */
    public static function normalize(string $value): string
    {
        $transliterated = strtr($value, self::ACCENT_MAP);
        $upper = strtoupper($transliterated);
        $clean = (string) preg_replace('/[^A-Z \'\-]/', '', $upper);

        return trim($clean);
    }

    /**
     * Whether the value is a structurally valid INSi trait, per the syntactic
     * rules of SEL-MP-043 §3.4.1 (first character not space/hyphen; space and
     * apostrophe not doubled or combined).
     */
    public static function isValid(string $value): bool
    {
        $normalized = self::normalize($value);

        if ($normalized === '') {
            return false;
        }

        if ($normalized[0] === ' ' || $normalized[0] === '-') {
            return false;
        }

        // A space and an apostrophe cannot be doubled or combined.
        if (str_contains($normalized, '  ')
            || str_contains($normalized, "''")
            || str_contains($normalized, " '")
            || str_contains($normalized, "' ")
        ) {
            return false;
        }

        return true;
    }
}
