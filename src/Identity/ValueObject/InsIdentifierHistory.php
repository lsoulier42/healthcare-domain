<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

/**
 * Immutable history of the INS identifiers of a patient, as returned by the
 * INSi téléservice (INSHISTO block) and preserved « à l'identique »
 * ([EXI REC 08]). The history may be empty (0..n occurrences).
 *
 * Factual references:
 * - GIE SESAM-Vitale, SEL-MP-043 v05.00.01, §3.4.2 (INSHISTO) ;
 * - Guide d'implémentation INS v3.0 (DNS, 12/2024), [EXI REC 08] et
 *   [EXI REC 07] (traçabilité et stockage à l'identique du retour INSi).
 */
final readonly class InsIdentifierHistory
{
    /**
     * @var list<HistoricalInsIdentifier>
     */
    public array $entries;

    /**
     * @param list<HistoricalInsIdentifier> $entries
     */
    public function __construct(array $entries = [])
    {
        $this->entries = array_values($entries);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function isEmpty(): bool
    {
        return $this->entries === [];
    }

    public function count(): int
    {
        return count($this->entries);
    }

    /**
     * Order-independent equality: the history is a multiset of entries.
     */
    public function equals(self $other): bool
    {
        if (count($this->entries) !== count($other->entries)) {
            return false;
        }

        foreach ($this->entries as $entry) {
            $matchFound = false;
            foreach ($other->entries as $otherEntry) {
                if ($entry->equals($otherEntry)) {
                    $matchFound = true;
                    break;
                }
            }

            if (!$matchFound) {
                return false;
            }
        }

        return true;
    }
}
