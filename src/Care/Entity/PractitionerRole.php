<?php

declare(strict_types=1);

namespace Healthcare\Care\Entity;

use Healthcare\Care\ValueObject\ProfessionCode;
use Healthcare\Care\ValueObject\SpecialtyCode;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Period;

final class PractitionerRole
{
    public function __construct(
        private readonly string $id,
        private readonly Practitioner $practitioner,
        private readonly Organization $organization,
        private ?ProfessionCode $profession = null,
        private ?SpecialtyCode $specialty = null,
        private ?Period $validityPeriod = null,
        private bool $active = true,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A practitioner role requires an identifier.');
        }

        $practitioner->addRole($this);
        $organization->addRole($this);
    }

    public function id(): string
    {
        return $this->id;
    }

    public function practitioner(): Practitioner
    {
        return $this->practitioner;
    }

    public function organization(): Organization
    {
        return $this->organization;
    }

    public function profession(): ?ProfessionCode
    {
        return $this->profession;
    }

    public function specialty(): ?SpecialtyCode
    {
        return $this->specialty;
    }

    public function validityPeriod(): ?Period
    {
        return $this->validityPeriod;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function deactivate(): void
    {
        $this->active = false;
    }

    public function activate(): void
    {
        $this->active = true;
    }
}
