<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

/**
 * ISO object identifier (OID), per the dotted-decimal arc syntax of
 * ITU-T X.660 / ISO/IEC 9834-1: at least two integer arcs separated by
 * dots, written without leading zeros (except the arc "0" itself).
 *
 * The structural root constraints are enforced:
 *
 * - the first arc is 0, 1 or 2;
 * - when the first arc is 0 or 1, the second arc is in 0..39.
 *
 * All arcs beyond the second are left open: unknown or future valid
 * OIDs under any registered subtree remain representable.
 */
final readonly class Oid extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return trim($value);
    }

    protected static function isValid(string $value): bool
    {
        $arcs = explode('.', $value);

        if (count($arcs) < 2) {
            return false;
        }

        foreach ($arcs as $arc) {
            if ($arc === '0') {
                continue;
            }

            if (preg_match('/^[1-9][0-9]*$/', $arc) !== 1) {
                return false;
            }
        }

        $first = (int) $arcs[0];
        $second = (int) $arcs[1];

        if ($first > 2) {
            return false;
        }

        if ($first < 2 && $second > 39) {
            return false;
        }

        return true;
    }
}
