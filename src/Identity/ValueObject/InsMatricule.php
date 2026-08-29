<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\ValueObject\AbstractStringValueObject;
use Healthcare\Kernel\ValueObject\Nir;

/**
 * INS matricule — the 15-character matricule component of the French
 * Identité Nationale de Santé, carrying its mod-97 control key: either
 * the RNIPP NIR or the provisional NIA (identical structure, e.g. issued
 * for people born abroad and not yet registered). A base NIR without its
 * control key is therefore not a valid INS matricule.
 *
 * An InsMatricule alone is not a complete INS identifier: it must be
 * paired with its InsAssigningAuthority (see InsIdentifier). Whether the
 * matricule is NIR- or NIA-based is not inferred from its digits.
 */
final readonly class InsMatricule extends AbstractStringValueObject
{
    protected static function normalize(string $value): string
    {
        return strtoupper((string) preg_replace('/[^0-9A-Z]/i', '', $value));
    }

    protected static function isValid(string $value): bool
    {
        return Nir::isStructurallyValid($value) && Nir::hasValidControlKey($value);
    }
}
