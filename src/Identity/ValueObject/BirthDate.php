<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\ValueObject\Date;

/**
 * Birth date of a patient, with the « fictive / incertaine » marker required
 * by the French identity rules.
 *
 * When the identity document provides an incomplete birth date, the RNIV
 * completion rules apply to build a calendar date (see
 * {@see self::fromPartial()}), and the resulting date must be distinguishable
 * from a real, exactly-known birth date. This marker may be carried in
 * information systems flows.
 *
 * Factual references:
 * - Guide d'implémentation INS dans les logiciels v3.0 (DNS, 12/2024),
 *   exigences [EXI ID 11] (règles de complétion : jour inconnu → 01/MM/AAAA,
 *   mois inconnu → JJ/01/AAAA, jour et mois inconnus → 31/12/AAAA) et
 *   [EXI ID 12] (dates incomplètes non acceptées dans le champ « date de
 *   naissance » de l'identité sanitaire — le logiciel doit pouvoir gérer
 *   séparément la date de l'identité de facturation) ;
 * - RNIV, EXI PP 19.
 */
final readonly class BirthDate
{
    private function __construct(
        public Date $date,
        public bool $estimated,
    ) {
    }

    /**
     * An exactly-known birth date.
     */
    public static function exact(Date $date): self
    {
        return new self($date, false);
    }

    /**
     * A birth date built by interpreting an incomplete source (the marker
     * does not make the date wrong — it flags it as interpreted).
     */
    public static function estimated(Date $date): self
    {
        return new self($date, true);
    }

    /**
     * Builds the birth date from a possibly partial calendar date, applying
     * the official completion rules ([EXI ID 11]):
     *
     * - only the day unknown → 01/MM/AAAA;
     * - only the month unknown → JJ/01/AAAA;
     * - day AND month unknown → 31/12/AAAA;
     * - both known → treated as an exact birth date.
     *
     * The year is required: when the year itself is imprecise, the RNIV lets
     * the user supply the year or decade compatible with the announced age —
     * a decision that belongs to the caller.
     */
    public static function fromPartial(int $year, ?int $month, ?int $day): self
    {
        $estimated = $month === null || $day === null;

        if ($month === null && $day === null) {
            $month = 12;
            $day = 31;
        } elseif ($month === null) {
            $month = 1;
        } elseif ($day === null) {
            $day = 1;
        }

        $date = new Date(sprintf('%04d-%02d-%02d', $year, $month, $day));

        return $estimated ? self::estimated($date) : self::exact($date);
    }

    public function isEstimated(): bool
    {
        return $this->estimated;
    }

    public function equals(self $other): bool
    {
        return $this->date->equals($other->date) && $this->estimated === $other->estimated;
    }
}
