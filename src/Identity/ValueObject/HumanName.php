<?php

declare(strict_types=1);

namespace Healthcare\Identity\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Immutable human name model, sized for French identity and
 * professional names (INS traits + usual names).
 */
final readonly class HumanName
{
    public string $familyName;

    /** @var list<string> */
    public array $givenNames;

    public ?string $usualName;

    /**
     * @param list<string> $givenNames
     */
    public function __construct(
        string $familyName,
        array $givenNames = [],
        ?string $usualName = null,
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

        $this->familyName = $normalizedFamily;
        $this->givenNames = $normalizedGiven;
        $this->usualName = $normalizedUsual === '' ? null : $normalizedUsual;
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
            && $this->usualName === $other->usualName;
    }

    public function __toString(): string
    {
        return $this->fullName();
    }
}
