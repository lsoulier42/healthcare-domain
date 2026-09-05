<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Immutable human name model, sized for French identity and
 * professional names (INS traits + usual names).
 *
 * The « prénom utilisé » (usual given name) completes the « nom utilisé »
 * (usualName): per [EXI ID 08], none of them may be auto-populated from the
 * birth names — filling them is a voluntary user action the software may
 * only facilitate.
 *
 * Factual references: Guide d'implémentation INS v3.0 (DNS, 12/2024),
 * exigence [EXI ID 08] ; RNIV, ex. EXI PP 17 / EXI PP 18.
 */
final readonly class HumanName
{
    public string $familyName;

    /** @var list<string> */
    public array $givenNames;

    public ?string $usualName;

    public ?string $usualGivenName;

    /**
     * @param list<string> $givenNames
     */
    public function __construct(
        string $familyName,
        array $givenNames = [],
        ?string $usualName = null,
        ?string $usualGivenName = null,
    ) {
        $normalizedFamily = trim($familyName);

        if ($normalizedFamily === '') {
            throw new InvalidValueObject('A human name requires a non-blank family name.');
        }

        $normalizedGiven = [];
        foreach ($givenNames as $givenName) {
            $given = trim($givenName);
            if ($given !== '') {
                $normalizedGiven[] = $given;
            }
        }

        $normalizedUsual = $usualName === null ? null : trim($usualName);
        $normalizedUsualGiven = $usualGivenName === null ? null : trim($usualGivenName);

        $this->familyName = $normalizedFamily;
        $this->givenNames = $normalizedGiven;
        $this->usualName = $normalizedUsual === '' ? null : $normalizedUsual;
        $this->usualGivenName = $normalizedUsualGiven === '' ? null : $normalizedUsualGiven;
    }

    public function firstGivenName(): ?string
    {
        return $this->givenNames[0] ?? null;
    }

    public function fullName(): string
    {
        return trim(sprintf('%s %s', implode(' ', $this->givenNames), $this->familyName));
    }

    public function equals(self $other): bool
    {
        return $this->familyName === $other->familyName
            && $this->givenNames === $other->givenNames
            && $this->usualName === $other->usualName
            && $this->usualGivenName === $other->usualGivenName;
    }

    public function __toString(): string
    {
        return $this->fullName();
    }
}
