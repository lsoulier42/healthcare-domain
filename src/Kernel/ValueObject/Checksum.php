<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

final class Checksum
{
    private function __construct()
    {
    }

    /** GS1 modulo 10, including the final check digit (weight 1). */
    public static function gs1(string $digits): bool
    {
        if (preg_match('/^[0-9]{2,}$/D', $digits) !== 1) {
            return false;
        }

        $sum = 0;
        $length = strlen($digits);
        for ($index = 0; $index < $length; $index++) {
            $digit = (int) $digits[$length - 1 - $index];
            $sum += $digit * ($index % 2 === 0 ? 1 : 3);
        }

        return $sum % 10 === 0;
    }

    public static function luhn(string $digits): bool
    {
        if ($digits === '' || preg_match('/^\d+$/', $digits) !== 1) {
            return false;
        }

        $sum = 0;
        $length = strlen($digits);
        for ($index = 0; $index < $length; $index++) {
            $digit = (int) $digits[$length - 1 - $index];
            if ($index % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }

    public static function siren(string $siren): bool
    {
        if (preg_match('/^\d{9}$/', $siren) !== 1) {
            return false;
        }

        $sum = 0;
        for ($index = 0; $index < 9; $index++) {
            $digit = (int) $siren[$index];
            if ($index % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        return $sum % 10 === 0;
    }
}
