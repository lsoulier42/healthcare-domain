<?php

declare(strict_types=1);

namespace Healthcare\Imaging\ValueObject;

use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;

/**
 * Imaging modality coded with DICOM (Coding Scheme Designator "DCM").
 */
final readonly class ModalityCode
{
    public function __construct(public Coding $coding)
    {
    }

    public static function fromDicom(string $code, ?string $display = null): self
    {
        return new self(new Coding(new CodeSystem('DCM'), $code, $display));
    }

    public function equals(self $other): bool
    {
        return $this->coding->equals($other->coding);
    }

    public function sameCodeAs(self $other): bool
    {
        return $this->coding->sameCodeAs($other->coding);
    }

    public function __toString(): string
    {
        return (string) $this->coding;
    }
}
