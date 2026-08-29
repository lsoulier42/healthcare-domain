<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\Entity\Patient;
use Healthcare\Clinical\ValueObject\SpecimenTypeCode;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Identifier;

/**
 * Minimal specimen model. Processing/aliquots/storage workflows belong
 * to the Laboratory module if needed.
 */
final class Specimen
{
    /** @var list<Identifier> */
    private array $identifiers = [];

    public function __construct(
        private readonly string $id,
        private ?Patient $patient = null,
        private ?SpecimenTypeCode $type = null,
        private ?DateTimeImmutable $collectedAt = null,
        private ?DateTimeImmutable $receivedAt = null,
        private ?CodeableConcept $collectionMethod = null,
        private ?CodeableConcept $bodySite = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A specimen requires an identifier.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function patient(): ?Patient
    {
        return $this->patient;
    }

    public function type(): ?SpecimenTypeCode
    {
        return $this->type;
    }

    public function collectedAt(): ?DateTimeImmutable
    {
        return $this->collectedAt;
    }

    public function receivedAt(): ?DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function collectionMethod(): ?CodeableConcept
    {
        return $this->collectionMethod;
    }

    public function bodySite(): ?CodeableConcept
    {
        return $this->bodySite;
    }

    public function changePatient(?Patient $patient): void
    {
        $this->patient = $patient;
    }

    public function changeType(?SpecimenTypeCode $type): void
    {
        $this->type = $type;
    }

    public function changeCollectedAt(?DateTimeImmutable $collectedAt): void
    {
        $this->collectedAt = $collectedAt;
    }

    public function changeReceivedAt(?DateTimeImmutable $receivedAt): void
    {
        $this->receivedAt = $receivedAt;
    }

    public function changeCollectionMethod(?CodeableConcept $collectionMethod): void
    {
        $this->collectionMethod = $collectionMethod;
    }

    public function changeBodySite(?CodeableConcept $bodySite): void
    {
        $this->bodySite = $bodySite;
    }

    /** @return list<Identifier> */
    public function identifiers(): array
    {
        return $this->identifiers;
    }

    public function addIdentifier(Identifier $identifier): void
    {
        foreach ($this->identifiers as $existing) {
            if ($existing->equals($identifier)) {
                return;
            }
        }

        $this->identifiers[] = $identifier;
    }

    public function removeIdentifier(Identifier $identifier): void
    {
        $this->identifiers = array_values(array_filter(
            $this->identifiers,
            static fn (Identifier $item): bool => !$item->equals($identifier),
        ));
    }
}
