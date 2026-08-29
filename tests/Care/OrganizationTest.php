<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\ContactPointType;
use Healthcare\Care\ValueObject\OrganizationCategoryCode;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Coding;
use Healthcare\Kernel\ValueObject\CodeSystem;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testCategoryWrapsAnExternalCoding(): void
    {
        $category = new OrganizationCategoryCode(new Coding(
            new CodeSystem('https://mos.esante.gouv.fr/NOS/TRE_G4-EnsembleActivite/FHIR/TRE-G4-EnsembleActivite'),
            'L02',
            'Laboratoire de biologie médicale',
        ));

        self::assertSame('L02', $category->coding->code);
        self::assertSame('Laboratoire de biologie médicale', $category->coding->display);
    }

    public function testCategoryAllowsMultipleCodingSystems(): void
    {
        $a = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:example:types'), 'PHARM'));
        $b = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:other'), 'PHARM'));

        self::assertFalse($a->equals($b));
    }

    public function testCategorySameCodeAsIgnoresDisplay(): void
    {
        $a = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:example:types'), 'PHARM', 'Pharmacie'));
        $b = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:example:types'), 'PHARM'));

        self::assertTrue($a->sameCodeAs($b));
    }

    public function testContactPointValidatesAndNormalizesItsValue(): void
    {
        $phone = new ContactPoint(ContactPointType::PHONE, '+33 1 23 45 67 89');
        $email = new ContactPoint(ContactPointType::EMAIL, '  ada@example.org ');

        self::assertSame('+33 1 23 45 67 89', $phone->value);
        self::assertSame('ada@example.org', $email->value);
    }

    public function testContactPointRejectsBlankValue(): void
    {
        $this->expectException(InvalidValueObject::class);
        new ContactPoint(ContactPointType::PHONE, '   ');
    }

    public function testContactPointRejectsInvalidEmail(): void
    {
        $this->expectException(InvalidValueObject::class);
        new ContactPoint(ContactPointType::EMAIL, 'not-an-email');
    }

    public function testContactPointEquality(): void
    {
        $a = new ContactPoint(ContactPointType::EMAIL, 'ada@example.org');
        $b = new ContactPoint(ContactPointType::EMAIL, 'ada@example.org');
        $c = new ContactPoint(ContactPointType::PHONE, '+33 1 23 45 67 89');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
