<?php

declare(strict_types=1);

namespace Healthcare\Medication\Entity;

use Healthcare\Kernel\Exception\InvalidDomainState;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Atc;
use Healthcare\Kernel\ValueObject\Cis;
use Healthcare\Medication\ValueObject\AdministrationRouteCode;
use Healthcare\Medication\ValueObject\MedicationComponent;
use Healthcare\Medication\ValueObject\PharmaceuticalFormCode;

/**
 * A medicinal product/specialty identified by its CIS, independent of
 * any particular packaging presentation.
 */
final class Medication
{
    /** @var list<MedicationComponent> */
    private array $components = [];

    /** @var list<MedicationPresentation> */
    private array $presentations = [];

    /** @var list<AdministrationRouteCode> */
    private array $administrationRoutes = [];

    /** @var list<Atc> */
    private array $classifications = [];

    public function __construct(
        private readonly string $id,
        private string $name,
        private ?Cis $cis = null,
        private ?PharmaceuticalFormCode $pharmaceuticalForm = null,
    ) {
        if (trim($id) === '' || trim($name) === '') {
            throw new InvalidValueObject('A medication requires an identifier and a name.');
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

    public function cis(): ?Cis
    {
        return $this->cis;
    }

    public function pharmaceuticalForm(): ?PharmaceuticalFormCode
    {
        return $this->pharmaceuticalForm;
    }

    public function rename(string $name): void
    {
        if (trim($name) === '') {
            throw new InvalidValueObject('A medication name cannot be empty.');
        }

        $this->name = $name;
    }

    public function assignCis(?Cis $cis): void
    {
        $this->cis = $cis;
    }

    public function changePharmaceuticalForm(?PharmaceuticalFormCode $pharmaceuticalForm): void
    {
        $this->pharmaceuticalForm = $pharmaceuticalForm;
    }

    /** @return list<AdministrationRouteCode> */
    public function administrationRoutes(): array
    {
        return $this->administrationRoutes;
    }

    public function addAdministrationRoute(AdministrationRouteCode $route): void
    {
        foreach ($this->administrationRoutes as $existing) {
            if ($existing->equals($route)) {
                return;
            }
        }

        $this->administrationRoutes[] = $route;
    }

    public function removeAdministrationRoute(AdministrationRouteCode $route): void
    {
        $this->administrationRoutes = array_values(array_filter(
            $this->administrationRoutes,
            static fn (AdministrationRouteCode $item): bool => !$item->equals($route),
        ));
    }

    /** @return list<MedicationComponent> */
    public function components(): array
    {
        return $this->components;
    }

    public function addComponent(MedicationComponent $component): void
    {
        foreach ($this->components as $existing) {
            if ($existing->equals($component)) {
                return;
            }
        }

        $this->components[] = $component;
    }

    public function removeComponent(MedicationComponent $component): void
    {
        $this->components = array_values(array_filter(
            $this->components,
            static fn (MedicationComponent $item): bool => !$item->equals($component),
        ));
    }

    /** @return list<Atc> */
    public function classifications(): array
    {
        return $this->classifications;
    }

    public function addClassification(Atc $atc): void
    {
        foreach ($this->classifications as $existing) {
            if ($existing->equals($atc)) {
                return;
            }
        }

        $this->classifications[] = $atc;
    }

    public function removeClassification(Atc $atc): void
    {
        $this->classifications = array_values(array_filter(
            $this->classifications,
            static fn (Atc $item): bool => !$item->equals($atc),
        ));
    }

    /** @return list<MedicationPresentation> */
    public function presentations(): array
    {
        return $this->presentations;
    }

    public function addPresentation(MedicationPresentation $presentation): void
    {
        if ($presentation->medication() !== $this) {
            throw new InvalidDomainState('A presentation must belong to this medication instance.');
        }

        foreach ($this->presentations as $existing) {
            if ($existing === $presentation) {
                return;
            }

            if ($existing->id() === $presentation->id()) {
                throw new InvalidDomainState('A different presentation already uses this identifier.');
            }
        }

        $this->presentations[] = $presentation;
    }

    public function removePresentation(MedicationPresentation $presentation): void
    {
        if ($presentation->medication() !== $this) {
            throw new InvalidDomainState('A presentation must belong to this medication instance.');
        }

        foreach ($this->presentations as $existing) {
            if ($existing->id() === $presentation->id() && $existing !== $presentation) {
                throw new InvalidDomainState('A different presentation already uses this identifier.');
            }
        }

        $this->presentations = array_values(array_filter(
            $this->presentations,
            static fn (MedicationPresentation $item): bool => $item !== $presentation,
        ));
    }
}
