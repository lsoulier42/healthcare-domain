<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\PatientConsistency;
use Healthcare\Clinical\ValueObject\ServiceRequestStatus;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;

/**
 * Generic clinical request/order (laboratory, imaging, procedure, ...).
 * Specialized payloads belong to specialized modules.
 */
final class ServiceRequest
{
    public function __construct(
        private readonly string $id,
        private readonly PatientReference $patient,
        private CodeableConcept $code,
        private ServiceRequestStatus $status,
        private ?PractitionerReference $requester = null,
        private ?OrganizationReference $performerOrganization = null,
        private ?Encounter $encounter = null,
        private ?DateTimeImmutable $authoredAt = null,
        private ?string $clinicalInformation = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A service request requires an identifier.');
        }

        PatientConsistency::assertCompatible($patient, $encounter?->patient());
    }

    public function id(): string
    {
        return $this->id;
    }

    public function patient(): PatientReference
    {
        return $this->patient;
    }

    public function code(): CodeableConcept
    {
        return $this->code;
    }

    public function status(): ServiceRequestStatus
    {
        return $this->status;
    }

    public function requester(): ?PractitionerReference
    {
        return $this->requester;
    }

    public function performerOrganization(): ?OrganizationReference
    {
        return $this->performerOrganization;
    }

    public function encounter(): ?Encounter
    {
        return $this->encounter;
    }

    public function authoredAt(): ?DateTimeImmutable
    {
        return $this->authoredAt;
    }

    public function clinicalInformation(): ?string
    {
        return $this->clinicalInformation;
    }

    public function changeCode(CodeableConcept $code): void
    {
        $this->code = $code;
    }

    public function changeStatus(ServiceRequestStatus $status): void
    {
        $this->status = $status;
    }

    public function changeRequester(?PractitionerReference $requester): void
    {
        $this->requester = $requester;
    }

    public function changePerformerOrganization(?OrganizationReference $performerOrganization): void
    {
        $this->performerOrganization = $performerOrganization;
    }

    public function changeEncounter(?Encounter $encounter): void
    {
        PatientConsistency::assertCompatible($this->patient, $encounter?->patient());
        $this->encounter = $encounter;
    }

    public function changeAuthoredAt(?DateTimeImmutable $authoredAt): void
    {
        $this->authoredAt = $authoredAt;
    }

    public function changeClinicalInformation(?string $clinicalInformation): void
    {
        $this->clinicalInformation = $clinicalInformation;
    }
}
