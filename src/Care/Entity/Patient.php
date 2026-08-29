<?php

declare(strict_types=1);

namespace Healthcare\Care\Entity;

use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Geographic\ValueObject\Address;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Kernel\Exception\InvalidValueObject;

/**
 * Application-level patient record. Composes a PatientIdentity for
 * identity semantics; ownership, history and persistence rules belong
 * to the consuming application.
 */
final class Patient
{
    /** @var list<ContactPoint> */
    private array $contactPoints = [];

    public function __construct(
        private readonly string $id,
        private PatientIdentity $identity,
        private ?Address $address = null,
    ) {
        if (trim($id) === '') {
            throw new InvalidValueObject('A patient requires an identifier.');
        }
    }

    public function id(): string
    {
        return $this->id;
    }

    public function identity(): PatientIdentity
    {
        return $this->identity;
    }

    public function replaceIdentity(PatientIdentity $identity): void
    {
        $this->identity = $identity;
    }

    public function address(): ?Address
    {
        return $this->address;
    }

    public function moveTo(?Address $address): void
    {
        $this->address = $address;
    }

    /** @return list<ContactPoint> */
    public function contactPoints(): array
    {
        return $this->contactPoints;
    }

    public function addContactPoint(ContactPoint $contactPoint): void
    {
        foreach ($this->contactPoints as $existing) {
            if ($existing->equals($contactPoint)) {
                return;
            }
        }

        $this->contactPoints[] = $contactPoint;
    }

    public function removeContactPoint(ContactPoint $contactPoint): void
    {
        $this->contactPoints = array_values(array_filter(
            $this->contactPoints,
            static fn (ContactPoint $item): bool => !$item->equals($contactPoint),
        ));
    }
}
