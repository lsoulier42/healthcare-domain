<?php

declare(strict_types=1);

namespace Healthcare\Imaging\Entity;

use DateTimeImmutable;
use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Patient;
use Healthcare\Clinical\Entity\Encounter;
use Healthcare\Clinical\Entity\ServiceRequest;
use Healthcare\Imaging\ValueObject\AccessionNumber;
use Healthcare\Imaging\ValueObject\ModalityCode;
use Healthcare\Imaging\ValueObject\StudyInstanceUid;
use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Lightweight imaging study: DICOM identifiers and minimal context.
 * Not a mirror of the FHIR ImagingStudy resource or the DICOM hierarchy.
 */
final class ImagingStudy
{
    /** @var list<ModalityCode> */
    private array $modalities = [];

    public function __construct(
        private readonly string $id,
        private readonly Patient $patient,
        private StudyInstanceUid $studyInstanceUid,
        private ?ServiceRequest $request = null,
        private ?Encounter $encounter = null,
        private ?AccessionNumber $accessionNumber = null,
        private ?DateTimeImmutable $startedAt = null,
        private ?Organization $organization = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('An imaging study requires an identifier.');
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

    public function studyInstanceUid(): StudyInstanceUid
    {
        return $this->studyInstanceUid;
    }

    public function request(): ?ServiceRequest
    {
        return $this->request;
    }

    public function encounter(): ?Encounter
    {
        return $this->encounter;
    }

    public function accessionNumber(): ?AccessionNumber
    {
        return $this->accessionNumber;
    }

    public function startedAt(): ?DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function organization(): ?Organization
    {
        return $this->organization;
    }

    public function changeStudyInstanceUid(StudyInstanceUid $studyInstanceUid): void
    {
        $this->studyInstanceUid = $studyInstanceUid;
    }

    public function changeRequest(?ServiceRequest $request): void
    {
        $this->request = $request;
    }

    public function changeEncounter(?Encounter $encounter): void
    {
        $this->encounter = $encounter;
    }

    public function changeAccessionNumber(?AccessionNumber $accessionNumber): void
    {
        $this->accessionNumber = $accessionNumber;
    }

    public function changeStartedAt(?DateTimeImmutable $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function changeOrganization(?Organization $organization): void
    {
        $this->organization = $organization;
    }

    /** @return list<ModalityCode> */
    public function modalities(): array
    {
        return $this->modalities;
    }

    public function addModality(ModalityCode $modality): void
    {
        foreach ($this->modalities as $existing) {
            if ($existing->equals($modality)) {
                return;
            }
        }

        $this->modalities[] = $modality;
    }

    public function removeModality(ModalityCode $modality): void
    {
        $this->modalities = array_values(array_filter(
            $this->modalities,
            static fn (ModalityCode $item): bool => !$item->equals($modality),
        ));
    }
}
