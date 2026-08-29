<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Confidence/status of a patient identity, per the four exclusive
 * functional statuses of the French RNIV (référentiel national
 * d'identitovigilance):
 *
 * - PROVISIONAL  (« identité provisoire »)
 * - RECOVERED    (« identité récupérée »)
 * - VALIDATED    (« identité validée »)
 * - QUALIFIED    (« identité qualifiée »)
 */
enum IdentityStatus: string
{
    case PROVISIONAL = 'provisional';
    case RECOVERED = 'recovered';
    case VALIDATED = 'validated';
    case QUALIFIED = 'qualified';

    public function label(): string
    {
        return match ($this) {
            self::PROVISIONAL => 'Identité provisoire',
            self::RECOVERED => 'Identité récupérée',
            self::VALIDATED => 'Identité validée',
            self::QUALIFIED => 'Identité qualifiée',
        };
    }
}
