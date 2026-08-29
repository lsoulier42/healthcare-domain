<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\Entity\Patient;
use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\ContactPointType;
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\IdentityStatus;
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class PatientTest extends TestCase
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

    private function ins(): InsIdentifier
    {
        return new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );
    }

    public function testPatientComposesProvisionalIdentity(): void
    {
        $patient = new Patient('patient-1', PatientIdentity::provisional($this->traits()));

        self::assertSame(AdministrativeGender::FEMALE, $patient->identity()->traits->gender);
        self::assertSame('LOVELACE', $patient->identity()->traits->birthFamilyName);
        self::assertSame('Ada', $patient->identity()->traits->firstBirthGivenName);
        self::assertSame(['Ada'], $patient->identity()->traits->birthGivenNames);
        self::assertSame('1815-12-10', (string) $patient->identity()->traits->birthDate);
        self::assertSame('99100', (string) $patient->identity()->traits->birthPlace);
        self::assertNull($patient->identity()->insIdentifier);
    }

    public function testIdentityStatusesFollowRnivVocabulary(): void
    {
        self::assertSame('provisional', IdentityStatus::PROVISIONAL->value);
        self::assertSame('recovered', IdentityStatus::RECOVERED->value);
        self::assertSame('validated', IdentityStatus::VALIDATED->value);
        self::assertSame('qualified', IdentityStatus::QUALIFIED->value);
        self::assertSame('Identité provisoire', IdentityStatus::PROVISIONAL->label());
        self::assertSame('Identité récupérée', IdentityStatus::RECOVERED->label());
        self::assertSame('Identité validée', IdentityStatus::VALIDATED->label());
        self::assertSame('Identité qualifiée', IdentityStatus::QUALIFIED->label());
    }

    public function testAdministrativeGenderUsesRnivValues(): void
    {
        self::assertSame('F', AdministrativeGender::FEMALE->value);
        self::assertSame('M', AdministrativeGender::MALE->value);
        self::assertSame('I', AdministrativeGender::UNKNOWN->value);
        self::assertSame('Indéterminé', AdministrativeGender::UNKNOWN->label());
    }

    public function testIdentityCanBeReplaced(): void
    {
        $patient = new Patient('patient-1', PatientIdentity::provisional($this->traits()));
        $qualified = PatientIdentity::qualified($this->traits(), $this->ins());

        $patient->replaceIdentity($qualified);

        self::assertSame(IdentityStatus::QUALIFIED, $patient->identity()->status);
        self::assertSame('185057512345673', (string) $patient->identity()->insIdentifier?->matricule);
    }

    public function testContactPointsCanBeAddedAndRemoved(): void
    {
        $patient = new Patient('patient-1', PatientIdentity::provisional($this->traits()));
        $email = new ContactPoint(ContactPointType::EMAIL, 'ada@example.org');

        $patient->addContactPoint($email);
        $patient->addContactPoint($email);

        self::assertSame([$email], $patient->contactPoints());

        $patient->removeContactPoint($email);
        self::assertSame([], $patient->contactPoints());
    }

    public function testContactPointsAreDeduplicatedByValue(): void
    {
        $patient = new Patient('patient-1', PatientIdentity::provisional($this->traits()));
        $first = new ContactPoint(ContactPointType::EMAIL, 'ada@example.org');
        $second = new ContactPoint(ContactPointType::EMAIL, 'ada@example.org');

        $patient->addContactPoint($first);
        $patient->addContactPoint($second);

        self::assertCount(1, $patient->contactPoints());

        $patient->removeContactPoint($second);
        self::assertCount(0, $patient->contactPoints());
    }

    public function testInvalidContactPointIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        new ContactPoint(ContactPointType::EMAIL, 'not-an-email');
    }
}
