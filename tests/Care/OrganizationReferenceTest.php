<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\OrganizationIdentity;
use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Finess;
use PHPUnit\Framework\TestCase;

final class OrganizationReferenceTest extends TestCase
{
    public function testBlankIdIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new OrganizationReference('   ');
    }

    public function testIdentitySnapshotIsOptional(): void
    {
        $reference = new OrganizationReference('organization-1');

        self::assertSame('organization-1', $reference->id);
        self::assertNull($reference->identity);
    }

    public function testCarriesOptionalIdentitySnapshot(): void
    {
        $reference = new OrganizationReference(
            id: 'organization-1',
            identity: new OrganizationIdentity(
                name: 'Clinique Exemple',
                finess: new Finess('010000014'),
            ),
        );

        self::assertNotNull($reference->identity);
        self::assertSame('Clinique Exemple', $reference->identity->name);
    }

    public function testEqualityIsBasedOnIdOnly(): void
    {
        $plain = new OrganizationReference('organization-1');
        $withIdentity = new OrganizationReference(
            id: 'organization-1',
            identity: new OrganizationIdentity('Clinique Exemple'),
        );

        self::assertTrue($plain->equals($withIdentity));
    }

    public function testEqualityDistinguishesIds(): void
    {
        $a = new OrganizationReference('organization-1');
        $b = new OrganizationReference('organization-2');

        self::assertFalse($a->equals($b));
    }
}
