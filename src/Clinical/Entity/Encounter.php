<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Practitioner;
use Healthcare\Care\Entity\Patient;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Period;

/**
 * Minimal encounter: patient, period, optional type and participation.
 */
final class Encounter
{
    /** @var list<Practitioner> */
    private array $participatingPractitioners = [];

    public function __construct(
        private readonly string $id,
        private readonly Patient $patient,
        private Period $period,
        private ?CodeableConcept $type = null,
        private ?Organization $organization = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('An encounter requires an identifier.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function patient(): Patient
    {
        return $this->patient;
    }

    public function period(): Period
    {
        return $this->period;
    }

    public function type(): ?CodeableConcept
    {
        return $this->type;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function changePeriod(Period $period): void
    {
        $this->period = $period;
    }

    public function changeType(?CodeableConcept $type): void
    {
        $this->type = $type;
    }

    public function changeOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    /** @return list<Practitioner> */
    public function participatingPractitioners(): array
    {
        return $this->participatingPractitioners;
    }

    public function addParticipatingPractitioner(Practitioner $practitioner): void
    {
        foreach ($this->participatingPractitioners as $existing) {
            if ($existing->id() === $practitioner->id()) {
                return;
            }
        }

        $this->participatingPractitioners[] = $practitioner;
    }

    public function removeParticipatingPractitioner(Practitioner $practitioner): void
    {
        $this->participatingPractitioners = array_values(array_filter(
            $this->participatingPractitioners,
            static fn (Practitioner $item): bool => $item->id() !== $practitioner->id(),
        ));
    }
}
