<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

/**
 * Lexical profiles of the INSi téléservice and INS datamatrix alphanumeric
 * fields.
 *
 * The reference documents do not apply exactly the same syntax to every
 * field, hence the three profiles:
 *
 * - FAMILY_NAME — « nom de naissance » (SEL-MP-043, EF_INS01_03 / EF_INS10_01):
 *   letters A-Z, space, apostrophe "'", single or double hyphen ("-" / "--");
 *   the first character may not be a space or a hyphen (an apostrophe is
 *   admitted in first position); a space and an apostrophe cannot be doubled
 *   or combined.
 * - GIVEN_NAME — « prénom » (SEL-MP-043, EF_INS10_02): letters A-Z, space,
 *   apostrophe and single hyphen only (no double hyphen); additionally the
 *   last character may not be a hyphen or an apostrophe.
 * - DATAMATRIX — the names carried by the INS datamatrix (ANS « Format
 *   datamatrix INS » v2.2, §5, S3 and S4): uppercase letters without accent
 *   or diacritic, hyphen and apostrophe allowed.
 */
enum InsiTraitProfile: string
{
    case FAMILY_NAME = 'family_name';
    case GIVEN_NAME = 'given_name';
    case DATAMATRIX = 'datamatrix';
}
