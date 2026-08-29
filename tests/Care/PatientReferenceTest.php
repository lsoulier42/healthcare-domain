<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class PatientReferenceTest extends TestCase
{
    private function traits(): StrictIdentityTraits
    {
        return new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    public function testBlankIdIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new PatientReference('   ');
    }

    public function testIdentitySnapshotIsOptional(): void
    {
        $reference = new PatientReference('patient-1');

        self::assertSame('patient-1', $reference->id);
        self::assertNull($reference->identity);
    }

    public function testCarriesOptionalIdentitySnapshot(): void
    {
        $reference = new PatientReference(
            id: 'patient-1',
            identity: PatientIdentity::provisional($this->traits()),
        );

        self::assertNotNull($reference->identity);
        self::assertSame('LOVELACE', $reference->identity->traits->birthFamilyName);
    }

    public function testEqualityIsBasedOnIdOnly(): void
    {
        $plain = new PatientReference('patient-1');
        $withIdentity = new PatientReference(
            id: 'patient-1',
            identity: PatientIdentity::provisional($this->traits()),
        );

        self::assertTrue($plain->equals($withIdentity));
        self::assertTrue($withIdentity->equals($plain));
    }

    public function testEqualityDistinguishesIds(): void
    {
        $a = new PatientReference('patient-1');
        $b = new PatientReference('patient-2');

        self::assertFalse($a->equals($b));
    }
}
