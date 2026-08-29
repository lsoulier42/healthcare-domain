<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\OrganizationIdentity;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Finess;
use Healthcare\Kernel\ValueObject\Siren;
use Healthcare\Kernel\ValueObject\Siret;
use PHPUnit\Framework\TestCase;

final class OrganizationIdentityTest extends TestCase
{
    public function testNameIsRequired(): void
    {
        $this->expectException(InvalidValueObject::class);
        new OrganizationIdentity('   ');
    }

    public function testCarriesNameAndOptionalIdentifiers(): void
    {
        $identity = new OrganizationIdentity(
            name: 'Clinique Exemple',
            finess: new Finess('010000014'),
        );

        self::assertSame('Clinique Exemple', $identity->name);
        self::assertSame('010000014', (string) $identity->finess);
        self::assertNull($identity->siren);
        self::assertNull($identity->siret);
    }

    public function testEqualityIsFullValueEquality(): void
    {
        $a = new OrganizationIdentity(
            name: 'Clinique Exemple',
            siren: new Siren('732829320'),
        );
        $b = new OrganizationIdentity(
            name: 'Clinique Exemple',
            siren: new Siren('732829320'),
        );

        self::assertTrue($a->equals($b));
    }

    public function testEqualityDistinguishesName(): void
    {
        $a = new OrganizationIdentity('Clinique Exemple');
        $b = new OrganizationIdentity('Autre Clinique');

        self::assertFalse($a->equals($b));
    }

    public function testEqualityDistinguishesIdentifiers(): void
    {
        $a = new OrganizationIdentity('Clinique Exemple');
        $b = new OrganizationIdentity('Clinique Exemple', siren: new Siren('732829320'));

        self::assertFalse($a->equals($b));
    }

    public function testSiretDerivesItsEmbeddedSiren(): void
    {
        $siret = new Siret('73282932000074');

        self::assertSame('732829320', (string) $siret->siren());
        self::assertTrue((new Siren('732829320'))->equals($siret->siren()));
    }

    public function testCoherentSirenAndSiretAreAccepted(): void
    {
        $identity = new OrganizationIdentity(
            name: 'Clinique Exemple',
            siren: new Siren('732829320'),
            siret: new Siret('73282932000074'),
        );

        self::assertSame('732829320', (string) $identity->siren);
        self::assertSame('73282932000074', (string) $identity->siret);
    }

    public function testIncoherentSirenAndSiretAreRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        new OrganizationIdentity(
            name: 'Clinique Exemple',
            siren: new Siren('732829320'),
            siret: new Siret('44306184100047'),
        );
    }
}
