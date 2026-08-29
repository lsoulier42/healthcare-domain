<?php

declare(strict_types=1);

namespace Healthcare\Clinical\Entity;

use DateTimeImmutable;
use Healthcare\Care\Entity\Patient;
use Healthcare\Clinical\ValueObject\ObservationStatus;
use Healthcare\Clinical\ValueObject\ObservationValue;
use Healthcare\Clinical\ValueObject\ObservationCode;
use Healthcare\Clinical\ValueObject\ReferenceRange;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Period;

/**
 * Generic clinical observation. The value is a typed union
 * (QuantityValue, TextValue, BooleanValue, CodedValue, IntegerValue,
 * RatioValue) — never mixed.
 */
final class Observation
{
    /** @var list<CodeableConcept> */
    private array $interpretation = [];

    /** @var list<ReferenceRange> */
    private array $referenceRanges = [];

    public function __construct(
        private readonly string $id,
        private readonly Patient $patient,
        private ObservationCode $code,
        private ObservationStatus $status,
        private ?ObservationValue $value = null,
        private DateTimeImmutable|Period|null $effective = null,
        private ?CodeableConcept $method = null,
        private ?Specimen $specimen = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('An observation requires an identifier.');
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

    public function code(): ObservationCode
    {
        return $this->code;
    }

    public function status(): ObservationStatus
    {
        return $this->status;
    }

    public function value(): ?ObservationValue
    {
        return $this->value;
    }

    public function effectiveAt(): ?DateTimeImmutable
    {
        return $this->effective instanceof DateTimeImmutable ? $this->effective : null;
    }

    public function effectivePeriod(): ?Period
    {
        return $this->effective instanceof Period ? $this->effective : null;
    }

    public function method(): ?CodeableConcept
    {
        return $this->method;
    }

    public function specimen(): ?Specimen
    {
        return $this->specimen;
    }

    public function changeCode(ObservationCode $code): void
    {
        $this->code = $code;
    }

    public function changeStatus(ObservationStatus $status): void
    {
        $this->status = $status;
    }

    public function changeValue(?ObservationValue $value): void
    {
        $this->value = $value;
    }

    /**
     * The effective[x] choice, cardinality 0..1: either a point in
     * time or a period, never both.
     */
    public function changeEffective(DateTimeImmutable|Period|null $effective): void
    {
        $this->effective = $effective;
    }

    public function changeMethod(?CodeableConcept $method): void
    {
        $this->method = $method;
    }

    public function changeSpecimen(?Specimen $specimen): void
    {
        $this->specimen = $specimen;
    }

    /** @return list<CodeableConcept> */
    public function interpretation(): array
    {
        return $this->interpretation;
    }

    public function addInterpretation(CodeableConcept $interpretation): void
    {
        foreach ($this->interpretation as $existing) {
            if ($existing->equals($interpretation)) {
                return;
            }
        }

        $this->interpretation[] = $interpretation;
    }

    public function removeInterpretation(CodeableConcept $interpretation): void
    {
        $this->interpretation = array_values(array_filter(
            $this->interpretation,
            static fn (CodeableConcept $item): bool => !$item->equals($interpretation),
        ));
    }

    /** @return list<ReferenceRange> */
    public function referenceRanges(): array
    {
        return $this->referenceRanges;
    }

    public function addReferenceRange(ReferenceRange $referenceRange): void
    {
        foreach ($this->referenceRanges as $existing) {
            if ($existing->equals($referenceRange)) {
                return;
            }
        }

        $this->referenceRanges[] = $referenceRange;
    }

    public function removeReferenceRange(ReferenceRange $referenceRange): void
    {
        $this->referenceRanges = array_values(array_filter(
            $this->referenceRanges,
            static fn (ReferenceRange $item): bool => !$item->equals($referenceRange),
        ));
    }
}
