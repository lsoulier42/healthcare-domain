<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\ValueObject\Date;

/**
 * One historical INS identifier returned by the INSi téléservice in the
 * INSHISTO block of a recovery response (WS_INS1/WS_INS2).
 *
 * Each entry carries the historical matricule (with its assigning authority
 * — a matricule never travels alone) and, when provided, the effective
 * period of that matricule (DateDeb / DateFin of the INSHISTO block).
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 « Guide d'intégration INSi » v05.00.01
 *   (19/12/2025), §3.4.2 (INSHISTO : IdIndividu/NumIdentifiant/Cle, OID,
 *   DateDeb, DateFin — 0..n) ;
 * - Guide d'implémentation INS v3.0 (DNS, 12/2024), exigence [EXI REC 08]
 *   (l'historique des matricules INS doit être conservé ; les informations
 *   retournées par le téléservice sont conservées à l'identique).
 *
 * Note: the reference bases limit the returned history (e.g. 10 changes
 * after 2006 under the régime général); that limit is an upstream property,
 * not enforced by this value object.
 */
final readonly class HistoricalInsIdentifier
{
    public function __construct(
        public InsIdentifier $identifier,
        public ?Date $effectiveFrom = null,
        public ?Date $effectiveUntil = null,
    ) {
    }

    public function equals(self $other): bool
    {
        $sameFrom = $this->effectiveFrom === null
            ? $other->effectiveFrom === null
            : $other->effectiveFrom !== null && $this->effectiveFrom->equals($other->effectiveFrom);

        $sameUntil = $this->effectiveUntil === null
            ? $other->effectiveUntil === null
            : $other->effectiveUntil !== null && $this->effectiveUntil->equals($other->effectiveUntil);

        return $this->identifier->equals($other->identifier) && $sameFrom && $sameUntil;
    }
}
