<?php

declare(strict_types=1);

namespace Healthcare\Imaging\ValueObject;

use Healthcare\Kernel\Exception\InvalidIdentifier;

/**
 * Generic DICOM UID (VR UI, NEMA PS3.5 §9):
 * - at most 64 characters;
 * - components separated by dots;
 * - each component is an unsigned decimal integer without leading
 *   zeros (except "0" itself);
 * - at least two components.
 */
final readonly class DicomUid
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (!self::isValid($normalized)) {
            throw new InvalidIdentifier('Invalid DICOM UID value.');
        }

        $this->value = $normalized;
    }

    public static function isValidValue(string $value): bool
    {
        return self::isValid(trim($value));
    }

    public static function tryFrom(string $value): ?self
    {
        return self::isValidValue($value) ? new self($value) : null;
    }

    private static function isValid(string $value): bool
    {
        if (strlen($value) > 64 || !str_contains($value, '.')) {
            return false;
        }

        $components = explode('.', $value);

        if (count($components) < 2) {
            return false;
        }

        foreach ($components as $component) {
            if ($component === '0') {
                continue;
            }

            if (preg_match('/^[1-9][0-9]*$/', $component) !== 1) {
                return false;
            }
        }

        return true;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
