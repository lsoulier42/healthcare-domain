<?php

declare(strict_types=1);

namespace Healthcare\Care\Entity;

use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\OrganizationCategoryCode;
use Healthcare\Geographic\ValueObject\Address;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Finess;
use Healthcare\Kernel\ValueObject\Siren;
use Healthcare\Kernel\ValueObject\Siret;

final class Organization
{
    /** @var list<PractitionerRole> */
    private array $roles = [];

    /** @var list<ContactPoint> */
    private array $contactPoints = [];

    public function __construct(
        private readonly string $id,
        private string $name,
        private ?Finess $finess = null,
        private ?Siren $siren = null,
        private ?Siret $siret = null,
        private ?Address $address = null,
        private ?OrganizationCategoryCode $category = null,
    ) {
        if (trim($id) === '' || trim($name) === '') {
            throw new InvalidValueObject('An organization requires an identifier and a name.');
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

    public function finess(): ?Finess
    {
        return $this->finess;
    }

    public function siren(): ?Siren
    {
        return $this->siren;
    }

    public function siret(): ?Siret
    {
        return $this->siret;
    }

    public function address(): ?Address
    {
        return $this->address;
    }

    public function category(): ?OrganizationCategoryCode
    {
        return $this->category;
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

    public function rename(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidValueObject('An organization name cannot be empty.');
        }

        $this->name = $name;
    }

    public function moveTo(?Address $address): void
    {
        $this->address = $address;
    }

    public function changeCategory(?OrganizationCategoryCode $category): void
    {
        $this->category = $category;
    }

    public function assignFiness(?Finess $finess): void
    {
        $this->finess = $finess;
    }

    public function assignSiren(?Siren $siren): void
    {
        $this->siren = $siren;
    }

    public function assignSiret(?Siret $siret): void
    {
        $this->siret = $siret;
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
