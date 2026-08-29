<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\ValueObject\DiagnosticReportStatus;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;

/**
 * Minimal diagnostic report usable by both laboratory and imaging
 * domains.
 */
final class DiagnosticReport
{
    /** @var list<Observation> */
    private array $results = [];

    /** @var list<Specimen> */
    private array $specimens = [];

    public function __construct(
        private readonly string $id,
        private readonly PatientReference $patient,
        private CodeableConcept $code,
        private DiagnosticReportStatus $status,
        private ?ServiceRequest $request = null,
        private ?Encounter $encounter = null,
        private ?DateTimeImmutable $issuedAt = null,
        private ?PractitionerReference $performer = null,
        private ?OrganizationReference $performerOrganization = null,
        private ?string $conclusion = null,
        private ?ClinicalDocument $document = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A diagnostic report requires an identifier.');
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

    public function code(): CodeableConcept
    {
        return $this->code;
    }

    public function status(): DiagnosticReportStatus
    {
        return $this->status;
    }

    public function request(): ?ServiceRequest
    {
        return $this->request;
    }

    public function encounter(): ?Encounter
    {
        return $this->encounter;
    }

    public function issuedAt(): ?DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function performer(): ?PractitionerReference
    {
        return $this->performer;
    }

    public function performerOrganization(): ?OrganizationReference
    {
        return $this->performerOrganization;
    }

    public function conclusion(): ?string
    {
        return $this->conclusion;
    }

    public function document(): ?ClinicalDocument
    {
        return $this->document;
    }

    public function changeCode(CodeableConcept $code): void
    {
        $this->code = $code;
    }

    public function changeStatus(DiagnosticReportStatus $status): void
    {
        $this->status = $status;
    }

    public function changeRequest(?ServiceRequest $request): void
    {
        $this->request = $request;
    }

    public function changeEncounter(?Encounter $encounter): void
    {
        $this->encounter = $encounter;
    }

    public function changeIssuedAt(?DateTimeImmutable $issuedAt): void
    {
        $this->issuedAt = $issuedAt;
    }

    public function changePerformer(?PractitionerReference $performer): void
    {
        $this->performer = $performer;
    }

    public function changePerformerOrganization(?OrganizationReference $performerOrganization): void
    {
        $this->performerOrganization = $performerOrganization;
    }

    public function changeConclusion(?string $conclusion): void
    {
        $this->conclusion = $conclusion;
    }

    public function changeDocument(?ClinicalDocument $document): void
    {
        $this->document = $document;
    }

    /** @return list<Observation> */
    public function results(): array
    {
        return $this->results;
    }

    public function addResult(Observation $observation): void
    {
        foreach ($this->results as $existing) {
            if ($existing->id() === $observation->id()) {
                return;
            }
        }

        $this->results[] = $observation;
    }

    public function removeResult(Observation $observation): void
    {
        $this->results = array_values(array_filter(
            $this->results,
            static fn (Observation $item): bool => $item->id() !== $observation->id(),
        ));
    }

    /** @return list<Specimen> */
    public function specimens(): array
    {
        return $this->specimens;
    }

    public function addSpecimen(Specimen $specimen): void
    {
        foreach ($this->specimens as $existing) {
            if ($existing->id() === $specimen->id()) {
                return;
            }
        }

        $this->specimens[] = $specimen;
    }

    public function removeSpecimen(Specimen $specimen): void
    {
        $this->specimens = array_values(array_filter(
            $this->specimens,
            static fn (Specimen $item): bool => $item->id() !== $specimen->id(),
        ));
    }
}
