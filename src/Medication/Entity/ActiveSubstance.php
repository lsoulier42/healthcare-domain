<?php

declare(strict_types=1);

namespace Healthcare\Medication\Entity;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Atc;
use Healthcare\Kernel\ValueObject\Coding;

final class ActiveSubstance
{
    public function __construct(
        private readonly string $id,
        private string $name,
        private ?Atc $atc = null,
        private ?Coding $codedIdentifier = null,
    ) {
        if (trim($id) === '' || trim($name) === '') {
            throw new InvalidValueObject('An active substance requires an identifier and a name.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function atc(): ?Atc
    {
        return $this->atc;
    }

    public function codedIdentifier(): ?Coding
    {
        return $this->codedIdentifier;
    }

    public function rename(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidValueObject('An active substance name cannot be empty.');
        }

        $this->name = $name;
    }

    public function assignAtc(?Atc $atc): void
    {
        $this->atc = $atc;
    }

    public function assignCodedIdentifier(?Coding $codedIdentifier): void
    {
        $this->codedIdentifier = $codedIdentifier;
    }
}
