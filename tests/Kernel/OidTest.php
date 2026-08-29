<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Kernel\ValueObject\Oid;
use PHPUnit\Framework\TestCase;

final class OidTest extends TestCase
{
    public function testValidOidsAreAccepted(): void
    {
        self::assertSame('1.2.250.1.213.1.4.8', (string) new Oid('1.2.250.1.213.1.4.8'));
        self::assertSame('2.25.123', (string) new Oid('2.25.123'));
        self::assertSame('0.0', (string) new Oid('0.0'));
        self::assertTrue(Oid::isValidValue('0.9.2342.19200300.100.1.1'));
        self::assertTrue(Oid::isValidValue('0.39.999')); // 0 root: second arc upper bound
        self::assertTrue(Oid::isValidValue('1.39.999')); // 1 root: second arc upper bound
        self::assertTrue(Oid::isValidValue('2.999.1')); // 2 root: second arc unbounded
    }

    public function testRootArcConstraintsAreEnforced(): void
    {
        // First arc must be 0, 1 or 2.
        self::assertFalse(Oid::isValidValue('3.1.2'));
        self::assertFalse(Oid::isValidValue('7.123.456'));
        self::assertNull(Oid::tryFrom('7.123.456'));

        // With a 0 or 1 first arc, the second arc must be in 0..39.
        self::assertFalse(Oid::isValidValue('1.999.42'));
        self::assertFalse(Oid::isValidValue('0.40.1'));
        self::assertNull(Oid::tryFrom('1.999.42'));
    }

    public function testInputIsTrimmed(): void
    {
        self::assertSame('1.2.3', (string) new Oid(' 1.2.3 '));
    }

    public function testEmptyValueIsRejected(): void
    {
        $this->expectException(InvalidIdentifier::class);
        new Oid('');
    }

    public function testMalformedOidsAreRejected(): void
    {
        self::assertFalse(Oid::isValidValue('1')); // single arc
        self::assertFalse(Oid::isValidValue('1..2')); // empty component
        self::assertFalse(Oid::isValidValue('1.2.03')); // leading zero
        self::assertFalse(Oid::isValidValue('1.2.3.')); // trailing dot
        self::assertFalse(Oid::isValidValue('.1.2.3')); // leading dot
        self::assertFalse(Oid::isValidValue('1.2a.3')); // non-digit component
        self::assertNull(Oid::tryFrom('1.2.03'));
    }

    public function testEqualityIsSemantic(): void
    {
        self::assertTrue((new Oid('1.2.3'))->equals(new Oid('1.2.3')));
        self::assertTrue((new Oid('1.2.3'))->equals(new Oid(' 1.2.3 ')));
        self::assertFalse((new Oid('1.2.3'))->equals(new Oid('1.2.4')));
        self::assertFalse((new Oid('1.2.3'))->equals(new Oid('1.2.3.0')));
    }
}
