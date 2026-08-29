<?php

declare(strict_types=1);

namespace Healthcare\Geographic\ValueObject;

use Healthcare\Kernel\Exception\InvalidValueObject;

final readonly class Address
{
    public function __construct(
        public string $streetLine1,
        public string $postalCode,
        public string $city,
        public CountryCode $country,
        public ?string $streetLine2 = null,
    ) {
        if (trim($streetLine1) === '' || trim($postalCode) === '' || trim($city) === '') {
            throw new InvalidValueObject('An address requires a street, postal code, and city.');
        }
    }

    public function equals(self $other): bool
    {
        return $this->streetLine1 === $other->streetLine1
            && $this->streetLine2 === $other->streetLine2
            && $this->postalCode === $other->postalCode
            && $this->city === $other->city
            && $this->country->equals($other->country);
    }

    public function text(): string
    {
        return implode(' ', array_filter([
            $this->streetLine1,
            $this->streetLine2,
            $this->postalCode,
            $this->city,
            (string) $this->country,
        ], static fn (?string $part): bool => $part !== null && trim($part) !== ''));
    }

    public function __toString(): string
    {
        return $this->text();
    }
}
