<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\IdentityStatus;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Kernel\Exception\InvalidDomainState;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;

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
 *   YYYY-MM-DD storage format) and S7 (COG birthplace, fixed 5);
 * - the <GS> separator (ASCII 29) closes a variable-length field that has
 *   NOT reached its maximum length and is not the last field. A variable
 *   field AT its maximum length carries no separator — as does any
 *   fixed-length field: the next identifier follows immediately.
 *
 * The builder only accepts a QUALIFIED identity: matricule + OID are traded
 * on health documents solely when the identity is qualified
 * ([EXI ID 29]), and the matricule never travels alone (it is read from
 * the identity's InsIdentifier). S3/S4 pass through
 * {@see InsiTraitsNormalizer} (DATAMATRIX profile).
 *
 * Factual reference:
 * - ANS (DNS), « INS — Format datamatrix » v2.2.20230926, §3 (structure and
 *   header), §5 (data identifiers S1-S7, sizes, GS rule, JJ-MM-AAAA date
 *   format, S3/S4 lexical profile), §6 (cartouche);
 * - Guide d'implémentation INS v3.0 (DNS, 12/2024), ex. EXI ID 29
 *   (transmission of matricule + OID only when qualified) and EXI DIF 02
 *   (first page of health documents);
 * - GIE SESAM-Vitale, SEL-MP-043 v05.00.01, §2 (lexical rules applied via
 *   the normalizer).
 * - Generated payloads must be validated with the ANS tool
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
     * Builds the full datamatrix payload (header + message zone) from a
     * qualified identity.
     *
     * {@see AdministrativeGender::UNKNOWN} cannot be encoded (INSi only
     * delivers F/M) and is rejected.
     *
     * @throws InvalidDomainState  when the identity is not QUALIFIED
     * @throws InvalidValueObject  on lexical or size violations
     */
    public static function fromQualifiedIdentity(PatientIdentity $identity): string
    {
        if ($identity->status !== IdentityStatus::QUALIFIED) {
            throw new InvalidDomainState(
                'The INS datamatrix only applies to a QUALIFIED identity ([EXI ID 29]).'
            );
        }

        $insIdentifier = $identity->insIdentifier;
        if ($insIdentifier === null) {
            // Unreachable by construction (QUALIFIED requires an INS), kept
            // defensive for static analysis.
            throw new InvalidDomainState('A qualified identity must carry an INS identifier.');
        }

        $traits = $identity->traits;

        if ($traits->gender === AdministrativeGender::UNKNOWN) {
            throw new InvalidValueObject(
                'Datamatrix INS requires a binary sexe (F or M); indeterminate (I) is not representable.'
            );
        }

        $givenNames = $traits->birthGivenNames ?? [$traits->firstBirthGivenName];
        $given = InsiTraitsNormalizer::normalizeDatamatrixName(implode(' ', $givenNames));
        $family = InsiTraitsNormalizer::normalizeDatamatrixName($traits->birthFamilyName);

        return self::HEADER
            . self::append('S1', (string) $insIdentifier->matricule, 15, 15, last: false)
            . self::append('S2', (string) $insIdentifier->authority->oid, 19, 20, last: false)
            . self::append('S3', $given, 1, 100, last: false)
            . self::append('S4', $family, 1, 100, last: false)
            . self::append('S5', $traits->gender->value, 1, 1, last: false)
            . self::append('S6', self::toDayMonthYear($traits->birthDate), 10, 10, last: false)
            . self::append('S7', (string) $traits->birthPlace, 5, 5, last: true);
    }

    /**
     * Appends one data block: identifier + value + <GS> when the field is a
     * variable-length field that has not reached its maximum length and is
     * not the last field (ANS v2.2, §3.3).
     */
    private static function append(string $id, string $value, int $min, int $max, bool $last): string
    {
        $length = strlen($value);

        if ($length < $min || $length > $max) {
            throw new InvalidValueObject(sprintf(
                'Datamatrix INS field %s must be %d..%d characters long (got %d).',
                $id,
                $min,
                $max,
                $length
            ));
        }

        $separator = !$last && $length < $max ? self::GS : '';

        return $id . $value . $separator;
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
