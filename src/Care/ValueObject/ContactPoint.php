<?php

declare(strict_types=1);

namespace Healthcare\Care\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

final readonly class ContactPoint
{
    public string $value;

    public function __construct(
        public ContactPointType $type,
        string $value,
    ) {
        $normalized = trim($value);

        if ($normalized === '') {
            throw new InvalidValueObject('A contact point requires a value.');
        }

        if ($type === ContactPointType::EMAIL && filter_var($normalized, FILTER_VALIDATE_EMAIL) === false) {
            throw new InvalidValueObject('A contact point email must be valid.');
        }

        if ($type === ContactPointType::PHONE && !preg_match('/^\+?[0-9][0-9 .()\-]{5,24}$/', $normalized)) {
            throw new InvalidValueObject('A contact point phone number must be valid.');
        }

        $this->value = $normalized;
    }

    public function equals(self $other): bool
    {
        return $this->type === $other->type && $this->value === $other->value;
    }
}
