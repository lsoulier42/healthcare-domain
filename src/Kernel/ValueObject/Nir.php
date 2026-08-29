<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

/**
 * NIR (Numéro d'Inscription au Répertoire) — the French social security
 * number managed by the INSEE, with or without its control key.
 *
 * Accepted structures follow the INSEE normalization of the NIR:
 * - sex: 1-2 (registered) or 3-4, 7-8 (in-progress / provisional registrations, e.g. NIA);
 * - birth month: 01-12, or the special codes 13, 20-42 and 50-99 used when
 *   the civil status record is incomplete or the birth occurred abroad;
 * - birth place: digits, or 2A / 2B for Corsica;
 * - 13 digits for the base number, or 15 digits with the mod-97 control key.
 */
final readonly class Nir extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9AB]/i', '', $value));
    }

    protected static function isValid(string $value): bool
    {
        if (!self::isStructurallyValid($value)) {
            return false;
        }

        return strlen($value) === 13 || self::hasValidControlKey($value);
    }

    /**
     * Checks the 13 or 15 digit NIR structure: sex, birth month (including the
     * INSEE special month codes) and birth place placeholders.
     */
    public static function isStructurallyValid(string $value): bool
    {
        $length = strlen($value);
        if ($length !== 13 && $length !== 15) {
            return false;
        }

        if (preg_match('/^[1-478]\d{4}(?:\d{5}|2[AB]\d{3})\d{3}$/', substr($value, 0, 13)) !== 1) {
            return false;
        }

        return preg_match('/^(?:0[1-9]|1[0-3]|2[0-9]|3[0-9]|4[0-2]|[5-9]\d)$/', substr($value, 3, 2)) === 1;
    }

    /**
     * Checks the mod-97 control key of a 15-digit NIR. Per the official rule,
     * Corsica departments are handled by replacing the letter with 0 and
     * subtracting 1,000,000 (2A) or 2,000,000 (2B) before the modulo.
     */
    public static function hasValidControlKey(string $value): bool
    {
        if (strlen($value) !== 15) {
            return false;
        }

        return self::expectedControlKey(substr($value, 0, 13)) === (int) substr($value, 13, 2);
    }

    private static function expectedControlKey(string $base): int
    {
        $numericBase = (int) strtr($base, ['A' => '0', 'B' => '0']);
        if (str_contains($base, 'A')) {
            $numericBase -= 1_000_000;
        } elseif (str_contains($base, 'B')) {
            $numericBase -= 2_000_000;
        }

        return 97 - ($numericBase % 97);
    }

    public function withoutControlKey(): string
    {
        return strlen($this->value) === 15 ? substr($this->value, 0, 13) : $this->value;
    }

    public function hasControlKey(): bool
    {
        return strlen($this->value) === 15;
    }

    public function sex(): string
    {
        return $this->value[0];
    }

    public function birthMonth(): string
    {
        return substr($this->value, 3, 2);
    }

    public function birthInseeCode(): string
    {
        return substr($this->value, 5, 5);
    }
}
