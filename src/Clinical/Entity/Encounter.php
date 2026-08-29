<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Period;

/**
 * Minimal encounter: patient, period, optional type and participation.
 */
final class Encounter
{
    /** @var list<PractitionerReference> */
    private array $participatingPractitioners = [];

    public function __construct(
        private readonly string $id,
        private readonly PatientReference $patient,
        private Period $period,
        private ?CodeableConcept $type = null,
        private ?OrganizationReference $organization = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('An encounter requires an identifier.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function patient(): PatientReference
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

    public function organization(): ?OrganizationReference
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

    public function changeOrganization(?OrganizationReference $organization): void
    {
        $this->organization = $organization;
    }

    /** @return list<PractitionerReference> */
    public function participatingPractitioners(): array
    {
        return $this->participatingPractitioners;
    }

    public function addParticipatingPractitioner(PractitionerReference $practitioner): void
    {
        foreach ($this->participatingPractitioners as $existing) {
            if ($existing->id === $practitioner->id) {
                return;
            }
        }

        $this->participatingPractitioners[] = $practitioner;
    }

    public function removeParticipatingPractitioner(PractitionerReference $practitioner): void
    {
        $this->participatingPractitioners = array_values(array_filter(
            $this->participatingPractitioners,
            static fn (PractitionerReference $item): bool => $item->id !== $practitioner->id,
        ));
    }
}
