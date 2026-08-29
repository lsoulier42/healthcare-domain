<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\Entity\Organization;
use Healthcare\Care\ValueObject\ContactPoint;
use Healthcare\Care\ValueObject\ContactPointType;
use Healthcare\Care\ValueObject\OrganizationCategoryCode;
use Healthcare\Kernel\ValueObject\Coding;
use Healthcare\Kernel\ValueObject\CodeSystem;
use PHPUnit\Framework\TestCase;

final class OrganizationTest extends TestCase
{
    public function testContactPointsCanBeAdded(): void
    {
        $organization = new Organization('o-1', 'Example Clinic');
        $phone = new ContactPoint(ContactPointType::PHONE, '+33 1 23 45 67 89');

        $organization->addContactPoint($phone);
        self::assertSame([$phone], $organization->contactPoints());
    }

    public function testCategoryIsOptional(): void
    {
        $organization = new Organization('o-1', 'Example Clinic');

        self::assertNull($organization->category());
    }

    public function testCategoryCanBeAssignedAndCleared(): void
    {
        $organization = new Organization('o-1', 'Example Clinic');
        $category = new OrganizationCategoryCode(new Coding(
            new CodeSystem('https://mos.esante.gouv.fr/NOS/TRE_G4-EnsembleActivite/FHIR/TRE-G4-EnsembleActivite'),
            'L02',
            'Laboratoire de biologie médicale',
        ));

        $organization->changeCategory($category);
        self::assertSame($category, $organization->category());

        $organization->changeCategory(null);
        self::assertNull($organization->category());
    }

    public function testCategoryAllowsMultipleCodingSystems(): void
    {
        $a = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:example:types'), 'PHARM'));
        $b = new OrganizationCategoryCode(new Coding(new CodeSystem('urn:other'), 'PHARM'));

        self::assertFalse($a->equals($b));
    }
}
