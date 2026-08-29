<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\ValueObject\DocumentContent;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;

/**
 * Domain document metadata, not a PDF generator. Content is a neutral
 * abstraction (text / media reference / external URI).
 */
final class ClinicalDocument
{
    public function __construct(
        private readonly string $id,
        private readonly PatientReference $patient,
        private CodeableConcept $type,
        private readonly PractitionerReference $author,
        private readonly DateTimeImmutable $createdAt,
        private ?DocumentContent $content = null,
        private ?string $title = null,
        private ?Encounter $encounter = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A clinical document requires an identifier.');
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

    public function type(): CodeableConcept
    {
        return $this->type;
    }

    public function title(): ?string
    {
        return $this->title;
    }

    public function author(): PractitionerReference
    {
        return $this->author;
    }

    public function createdAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function content(): ?DocumentContent
    {
        return $this->content;
    }

    public function encounter(): ?Encounter
    {
        return $this->encounter;
    }

    public function changeType(CodeableConcept $type): void
    {
        $this->type = $type;
    }

    public function changeTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function changeContent(?DocumentContent $content): void
    {
        $this->content = $content;
    }

    public function changeEncounter(?Encounter $encounter): void
    {
        $this->encounter = $encounter;
    }
}
