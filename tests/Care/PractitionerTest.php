<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\Entity\Practitioner;
use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\ContactPointType;
use Healthcare\Care\ValueObject\ProfessionalTitle;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\ValueObject\Rpps;
use PHPUnit\Framework\TestCase;

final class PractitionerTest extends TestCase
{
    public function testContactPointsCanBeAdded(): void
    {
        $practitioner = new Practitioner('p-1', new HumanName('Lovelace', ['Ada']));
        $phone = new ContactPoint(ContactPointType::PHONE, '+33 1 23 45 67 89');

        $practitioner->addContactPoint($phone);
        self::assertSame([$phone], $practitioner->contactPoints());
    }

    public function testPractitionerUsesOptionalProfessionalTitle(): void
    {
        $practitioner = new Practitioner(
            'p-1',
            new HumanName('Lovelace', ['Ada']),
            new Rpps('12345678901'),
            ProfessionalTitle::DOCTOR,
        );

        self::assertSame(ProfessionalTitle::DOCTOR, $practitioner->professionalTitle());
        self::assertSame('Dr', $practitioner->professionalTitle()->abbreviation());
        self::assertSame('Docteur', $practitioner->professionalTitle()->label());

        $practitioner->changeProfessionalTitle(ProfessionalTitle::PROFESSOR);
        self::assertNotNull($practitioner->professionalTitle());
        self::assertSame('Pr', $practitioner->professionalTitle()->abbreviation());

        $practitioner->changeProfessionalTitle(null);
        self::assertNull($practitioner->professionalTitle());
    }

    public function testPractitionerCanExistWithoutRpps(): void
    {
        $practitioner = new Practitioner('p-2', new HumanName('Lovelace', ['Ada']));

        self::assertNull($practitioner->rpps());

        $practitioner->assignRpps(new Rpps('12345678901'));
        self::assertSame('12345678901', (string) $practitioner->rpps());
    }

    public function testPractitionerUsesHumanName(): void
    {
        $practitioner = new Practitioner('p-1', new HumanName('Lovelace', ['Ada', 'Byron']));

        self::assertSame('Ada', $practitioner->name()->firstGivenName());
        self::assertSame('Lovelace', $practitioner->name()->familyName);
        self::assertSame('Ada Byron Lovelace', (string) $practitioner->name());

        $practitioner->rename(new HumanName('King', ['Augusta', 'Ada']));
        self::assertSame('King', $practitioner->name()->familyName);
    }
}
