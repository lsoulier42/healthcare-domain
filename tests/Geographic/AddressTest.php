<?php

declare(strict_types=1);

namespace Healthcare\Tests\Geographic;

use Healthcare\Geographic\ValueObject\Address;
use Healthcare\Geographic\ValueObject\CountryCode;
use PHPUnit\Framework\TestCase;

final class AddressTest extends TestCase
{
    public function testAddressRendersItsComponents(): void
    {
        $address = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));

        self::assertSame('1 rue Exemple 75001 Paris FR', (string) $address);
    }

    public function testEqualsReturnsTrueForIdenticalAddresses(): void
    {
        $a = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));
        $b = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));

        self::assertTrue($a->equals($b));
    }

    public function testEqualsReturnsFalseForDifferentAddresses(): void
    {
        $a = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));
        $b = new Address('2 rue Autre', '75002', 'Lyon', new CountryCode('fr'));

        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsFalseWhenOnlyStreetLine2Differs(): void
    {
        $a = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'), 'Bis');
        $b = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'), 'Ter');

        self::assertFalse($a->equals($b));
    }

    public function testEqualsReturnsTrueWhenBothStreetLine2AreNull(): void
    {
        $a = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));
        $b = new Address('1 rue Exemple', '75001', 'Paris', new CountryCode('fr'));

        self::assertTrue($a->equals($b));
    }
}
