<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class StrictIdentityTraitsTest extends TestCase
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

    public function testFirstGivenNameIsKnownWhileFullListIsUnknown(): void
    {
        // The 5 minimal identity traits: family name, first given name,
        // birth date, gender, birthplace. The full list is not required.
        $traits = new StrictIdentityTraits(
            birthFamilyName: 'DUPONT',
            firstBirthGivenName: 'Jean',
            birthGivenNames: null,
            birthDate: new Date('1970-01-01'),
            gender: AdministrativeGender::MALE,
            birthPlace: new CogCode('75056'),
        );

        self::assertSame('Jean', $traits->firstBirthGivenName);
        self::assertNull($traits->birthGivenNames);
    }

    public function testValidConstructionNormalizesNames(): void
    {
        $traits = new StrictIdentityTraits(
            birthFamilyName: '  Lovelace ',
            firstBirthGivenName: ' Ada ',
            birthGivenNames: [' Ada ', ''],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );

        self::assertSame('Lovelace', $traits->birthFamilyName);
        self::assertSame('Ada', $traits->firstBirthGivenName);
        self::assertSame(['Ada'], $traits->birthGivenNames);
    }

    public function testEmptyFamilyNameIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new StrictIdentityTraits(
            birthFamilyName: '   ',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    public function testBlankFirstGivenNameIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: ' ',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    public function testEmptyGivenNameListIsRejectedWhenProvided(): void
    {
        $this->expectException(InvalidValueObject::class);
        new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: [],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    public function testBlankGivenNameListIsRejectedWhenProvided(): void
    {
        $this->expectException(InvalidValueObject::class);
        new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: [' ', ''],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
    }

    public function testEqualityRequiresEveryTrait(): void
    {
        $same = $this->traits();

        self::assertTrue($this->traits()->equals($same));
        self::assertFalse($this->traits()->equals(new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada', 'Byron'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        )));
        self::assertFalse($this->traits()->equals(new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-11'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        )));
        self::assertFalse($this->traits()->equals(new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: null,
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        )));
        self::assertFalse($this->traits()->equals(new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Augusta',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        )));
    }
}
