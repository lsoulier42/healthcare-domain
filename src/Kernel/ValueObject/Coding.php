<?php

declare(strict_types=1);

namespace Healthcare\Kernel\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Generic immutable coded concept (code + coding system).
 *
 * Equality semantics:
 * - equals() is strict and transitive: system + code + version must
 *   all match (a missing version is treated as a distinct value from a
 *   present one). Display labels never participate.
 * - sameCodeAs() ignores the version and compares the code identity
 *   (system + code) only.
 */
final readonly class Coding
{
    public string $code;

    public ?string $display;

    public ?string $version;

    public function __construct(
        public CodeSystem $system,
        string $code,
        ?string $display = null,
        ?string $version = null,
    ) {
        $normalizedCode = trim($code);

        if ($normalizedCode === '') {
            throw new InvalidValueObject('A coded concept requires a non-blank code.');
        }

        $this->code = $normalizedCode;
        $this->display = $display === null || trim($display) === '' ? null : trim($display);
        $this->version = $version === null || trim($version) === '' ? null : trim($version);
    }

    public function equals(self $other): bool
    {
        return $this->system->equals($other->system)
            && $this->code === $other->code
            && $this->version === $other->version;
    }

    public function sameCodeAs(self $other): bool
    {
        return $this->system->equals($other->system) && $this->code === $other->code;
    }

    public function __toString(): string
    {
        return sprintf('%s|%s', (string) $this->system, $this->code);
    }
}
