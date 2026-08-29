<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Patient;
use Healthcare\Care\Entity\Practitioner;
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
        private readonly Patient $patient,
        private CodeableConcept $code,
        private ServiceRequestStatus $status,
        private ?Practitioner $requester = null,
        private ?Organization $performerOrganization = null,
        private ?Encounter $encounter = null,
        private ?DateTimeImmutable $authoredAt = null,
        private ?string $clinicalInformation = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A service request requires an identifier.');
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

    public function code(): CodeableConcept
    {
        return $this->code;
    }

    public function status(): ServiceRequestStatus
    {
        return $this->status;
    }

    public function requester(): ?Practitioner
    {
        return $this->requester;
    }

    public function performerOrganization(): ?Organization
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

    public function changeRequester(?Practitioner $requester): void
    {
        $this->requester = $requester;
    }

    public function changePerformerOrganization(?Organization $performerOrganization): void
    {
        $this->performerOrganization = $performerOrganization;
    }

    public function changeEncounter(?Encounter $encounter): void
    {
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
