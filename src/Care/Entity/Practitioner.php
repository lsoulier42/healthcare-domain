<?php

declare(strict_types=1);

namespace Healthcare\Care\Entity;

use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\ProfessionalTitle;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Rpps;

final class Practitioner
{
    /** @var list<PractitionerRole> */
    private array $roles = [];

    /** @var list<ContactPoint> */
    private array $contactPoints = [];

    public function __construct(
        private readonly string $id,
        private HumanName $name,
        private ?Rpps $rpps = null,
        private ?ProfessionalTitle $professionalTitle = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A practitioner requires an identifier.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function name(): HumanName
    {
        return $this->name;
    }

    public function rename(HumanName $name): void
    {
        $this->name = $name;
    }

    public function rpps(): ?Rpps
    {
        return $this->rpps;
    }

    public function assignRpps(?Rpps $rpps): void
    {
        $this->rpps = $rpps;
    }

    public function professionalTitle(): ?ProfessionalTitle
    {
        return $this->professionalTitle;
    }

    public function changeProfessionalTitle(?ProfessionalTitle $professionalTitle): void
    {
        $this->professionalTitle = $professionalTitle;
    }

    /** @return list<ContactPoint> */
    public function contactPoints(): array
    {
        return $this->contactPoints;
    }

    public function addContactPoint(ContactPoint $contactPoint): void
    {
        foreach ($this->contactPoints as $existing) {
            if ($existing->equals($contactPoint)) {
                return;
            }
        }

        $this->contactPoints[] = $contactPoint;
    }

    public function removeContactPoint(ContactPoint $contactPoint): void
    {
        $this->contactPoints = array_values(array_filter(
            $this->contactPoints,
            static fn (ContactPoint $item): bool => !$item->equals($contactPoint),
        ));
    }

    /** @return list<PractitionerRole> */
    public function roles(): array
    {
        return $this->roles;
    }

    public function addRole(PractitionerRole $role): void
    {
        foreach ($this->roles as $existing) {
            if ($existing->id() === $role->id()) {
                return;
            }
        }

        $this->roles[] = $role;
    }

    public function removeRole(PractitionerRole $role): void
    {
        $this->roles = array_values(array_filter(
            $this->roles,
            static fn (PractitionerRole $item): bool => $item->id() !== $role->id(),
        ));
    }
}
