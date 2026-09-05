<?php

declare(strict_types=1);

namespace Healthcare\Identity\Service;

/**
 * Verifies the coherence between the « premier prénom de naissance » field
 * and the beginning of the « liste des prénoms de naissance », as required by
 * the French identity rules.
 *
 * The first birth given name must be coherent with the START of the birth
 * given-name list; when comparing, hyphens and apostrophes must not be
 * considered different from a space: "JEAN CHRISTOPHE", "JEAN-CHRISTOPHE"
 * and "JEAN-CHRISTOPHE-PIERRE" are all coherent with a list starting with
 * "JEAN CHRISTOPHE PIERRE", whereas "CHRISTOPHE" or "PIERRE" alone are not.
 *
 * The first birth given name stays user-editable whatever the identity status
 * as long as this coherence holds; an alert must be raised on incoherence.
 *
 * Factual references:
 * - Guide d'implémentation INS dans les logiciels v3.0 (DNS, 12/2024),
 *   exigence [EXI ID 10] ;
 * - RNIV, ex. EXI SI 29 et EXI SI 30.
 */
final class FirstGivenNameConsistency
{
    private const SEPARATOR_MAP = ['-' => ' ', "'" => ' '];

    private function __construct()
    {
    }

    /**
     * Whether the first birth given name is consistent with the start of the
     * birth given-name list, separators (hyphen/apostrophe) being equivalent
     * to spaces.
     *
     * @param list<string> $birthGivenNames
     */
    public static function isConsistent(string $firstBirthGivenName, array $birthGivenNames): bool
    {
        $first = self::collapse($firstBirthGivenName);

        if ($first === '' || $birthGivenNames === []) {
            return false;
        }

        $list = self::collapse(implode(' ', $birthGivenNames));

        return $list === $first || str_starts_with($list, $first . ' ');
    }

    /**
     * Uppercases and normalizes separators (hyphen and apostrophe become
     * spaces, repeated whitespace collapsed).
     */
    private static function collapse(string $value): string
    {
        $normalized = strtr($value, self::SEPARATOR_MAP);

        return strtoupper((string) preg_replace('/\s+/', ' ', trim($normalized)));
    }
}
