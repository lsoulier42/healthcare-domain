<?php

declare(strict_types=1);

namespace Healthcare\Imaging\ValueObject;

/**
 * DICOM Study Instance UID. A distinct type so it cannot be
 * accidentally interchanged with series or SOP instance UIDs.
 */
final readonly class StudyInstanceUid
{
    public function __construct(public DicomUid $uid)
    {
    }

    public static function tryFrom(string $value): ?self
    {
        $uid = DicomUid::tryFrom($value);

        return $uid === null ? null : new self($uid);
    }

    public function equals(self $other): bool
    {
        return $this->uid->equals($other->uid);
    }

    public function __toString(): string
    {
        return $this->uid->value;
    }
}
