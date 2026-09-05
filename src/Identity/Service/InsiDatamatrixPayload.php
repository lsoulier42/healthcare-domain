<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use Healthcare\Kernel\ValueObject\Oid;

/**
 * Builds the INS datamatrix payload — the 2D barcode content printed on
 * French health documents alongside the qualified INS (ordonnances,
 * biology results, discharge summaries…).
 *
 * The payload follows the ANS specification « Format datamatrix INS »
 * v2.2.20230926:
 *
 * - a fixed 26-character header, C40-encoded: marqueur "IS" (positions 0-1),
 *   version "01" (positions 2-3), 22 reserved "0" characters (positions
 *   4-25). This class emits the header raw; C40 encoding and the graphical
 *   rendering (ISO/IEC 16022 square DataMatrix, ECC200, quiet zone ≥ 1
 *   module, « INS à scanner » marking) are the rendering layer's concern;
 * - a message zone made of data blocks: two-character identifiers
 *   S1 (matricule INS, fixed 15), S2 (OID, 19-20), S3 (birth given-name
 *   list), S4 (birth family name), S5 (sexe, fixed 1), S6 (birth date,
 *   fixed 10, format JJ-MM-AAAA — note the inverted date order versus the
 *   YYYY-MM-DD storage format) and optional S7 (COG birthplace, fixed 5);
 * - the <GS> separator (ASCII 29) closes variable-length fields that have
 *   not reached their maximum length and are not the last field; fixed-length
 *   fields need no separator.
 *
 * S3 and S4 follow the INSi lexical profile (uppercase, no diacritic, hyphen
 * and apostrophe allowed): values are passed through
 * {@see InsiTraitsNormalizer} before encoding.
 *
 * Factual reference:
 * - ANS (DNS), « INS — Format datamatrix » v2.2.20230926, §3 (structure and
 *   header), §5 (data identifiers S1-S7 and GS rule), §6 (cartouche).
 * - The header of the IAB for the graphical layer also requires validating
 *   the produced payload with the ANS validation tool
 *   (https://interop.esante.gouv.fr/datamatrixins/) in the CI.
 */
final class InsiDatamatrixPayload
{
    /** Fixed 26-character header: "IS" + version "01" + 22 reserved zeros. */
    private const HEADER = 'IS010000000000000000000000';

    private const GS = "\x1D"; // <GS>, ASCII 29

    private function __construct()
    {
    }

    /**
     * Builds the full datamatrix payload (header + message zone).
     *
     * {@see AdministrativeGender::UNKNOWN} cannot be encoded (INSi only
     * delivers F/M) and is rejected.
     *
     * @param string $birthGivenNames the complete birth given-name list,
     *                                space-separated (S3)
     * @param string $birthFamilyName the birth family name (S4)
     */
    public static function build(
        InsMatricule $matricule,
        Oid $oid,
        string $birthGivenNames,
        string $birthFamilyName,
        AdministrativeGender $gender,
        Date $birthDate,
        ?CogCode $birthPlace = null,
    ): string {
        $givenNames = InsiTraitsNormalizer::normalize($birthGivenNames);
        $familyName = InsiTraitsNormalizer::normalize($birthFamilyName);

        if (!InsiTraitsNormalizer::isValid($givenNames) || !InsiTraitsNormalizer::isValid($familyName)) {
            throw new InvalidValueObject(
                'Datamatrix INS names must follow the INSi lexical profile (uppercase A-Z, space, apostrophe, hyphens).'
            );
        }

        if ($gender === AdministrativeGender::UNKNOWN) {
            throw new InvalidValueObject(
                'Datamatrix INS requires a binary sexe (F or M); indeterminate (I) is not representable.'
            );
        }

        $dayMonthYear = self::toDayMonthYear($birthDate);

        $message = 'S1' . $matricule
            . 'S2' . $oid . self::GS
            . 'S3' . $givenNames . self::GS
            . 'S4' . $familyName . self::GS
            . 'S5' . $gender->value
            . 'S6' . $dayMonthYear;

        if ($birthPlace !== null) {
            $message .= 'S7' . $birthPlace;
        }

        return self::HEADER . $message;
    }

    /**
     * Converts the stored calendar date (YYYY-MM-DD) to the datamatrix
     * format JJ-MM-AAAA.
     */
    private static function toDayMonthYear(Date $birthDate): string
    {
        [$year, $month, $day] = explode('-', (string) $birthDate);

        return sprintf('%s-%s-%s', $day, $month, $year);
    }
}