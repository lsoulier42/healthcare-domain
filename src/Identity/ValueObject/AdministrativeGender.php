<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Administrative sex/gender as used by French healthcare identity
 * workflows (RNIV: F, M, I — indéterminé).
 *
 * This is the administrative field of identity records; it is not a
 * comprehensive model of gender identity.
 */
enum AdministrativeGender: string
{
    case MALE = 'M';
    case FEMALE = 'F';
    case UNKNOWN = 'I';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Masculin',
            self::FEMALE => 'Féminin',
            self::UNKNOWN => 'Indéterminé',
        };
    }
}
