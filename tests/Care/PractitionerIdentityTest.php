<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\PractitionerIdentity;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\ValueObject\Adeli;
use Healthcare\Kernel\ValueObject\Rpps;
use PHPUnit\Framework\TestCase;

final class PractitionerIdentityTest extends TestCase
{
    public function testNameIsRequiredAndRppsAdeliAreOptional(): void
    {
        $identity = new PractitionerIdentity(new HumanName('Curie', ['Marie']));

        self::assertSame('Curie', $identity->name->familyName);
        self::assertNull($identity->rpps);
        self::assertNull($identity->adeli);
    }

    public function testCarriesRpps(): void
    {
        $identity = new PractitionerIdentity(
            new HumanName('Curie', ['Marie']),
            rpps: new Rpps('12345678901'),
        );

        self::assertSame('12345678901', (string) $identity->rpps);
        self::assertNull($identity->adeli);
    }

    public function testCarriesAdeli(): void
    {
        $identity = new PractitionerIdentity(
            new HumanName('Curie', ['Marie']),
            adeli: new Adeli('12 345 6789'),
        );

        self::assertSame('123456789', (string) $identity->adeli);
        self::assertNull($identity->rpps);
    }

    public function testEqualityIsFullValueEquality(): void
    {
        $a = new PractitionerIdentity(
            new HumanName('Curie', ['Marie']),
            rpps: new Rpps('12345678901'),
            adeli: new Adeli('123456789'),
        );
        $b = new PractitionerIdentity(
            new HumanName('Curie', ['Marie']),
            rpps: new Rpps('12345678901'),
            adeli: new Adeli('123456789'),
        );

        self::assertTrue($a->equals($b));
    }

    public function testEqualityDistinguishesName(): void
    {
        $a = new PractitionerIdentity(new HumanName('Curie', ['Marie']));
        $b = new PractitionerIdentity(new HumanName('Curie', ['Pierre']));

        self::assertFalse($a->equals($b));
    }

    public function testEqualityDistinguishesIdentifiers(): void
    {
        $a = new PractitionerIdentity(
            new HumanName('Curie', ['Marie']),
            rpps: new Rpps('12345678901'),
        );
        $b = new PractitionerIdentity(new HumanName('Curie', ['Marie']));

        self::assertFalse($a->equals($b));
    }
}
