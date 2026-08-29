<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class InsMatriculeTest extends TestCase
{
    public function testRequiresFullMatriculeWithControlKey(): void
    {
        self::assertSame('185057512345673', (string) new InsMatricule('185057512345673'));
        self::assertTrue(InsMatricule::isValidValue('1 85 05 75 123 456 73'));
        self::assertNull(InsMatricule::tryFrom('1850575123456')); // base NIR without control key
        self::assertNull(InsMatricule::tryFrom('185057512345600')); // invalid control key
        $this->expectException(InvalidValueObject::class);
        new InsMatricule('1850575123456');
    }

    public function testAcceptsProvisionalNia(): void
    {
        self::assertSame('885997512345663', (string) new InsMatricule('885997512345663'));
        self::assertSame('781102A12500908', (string) new InsMatricule('781102A12500908'));
    }

    public function testTryFromReturnsNullOnInvalidValue(): void
    {
        self::assertNull(InsMatricule::tryFrom('123'));
    }

    public function testTryFromReturnsInstanceOnValidValue(): void
    {
        self::assertInstanceOf(InsMatricule::class, InsMatricule::tryFrom('185057512345673'));
    }

    public function testEqualityIsSemantic(): void
    {
        self::assertTrue((new InsMatricule('185057512345673'))->equals(new InsMatricule('185057512345673')));
        self::assertTrue((new InsMatricule('185057512345673'))->equals(new InsMatricule('1 85 05 75 123 456 73')));
        self::assertFalse((new InsMatricule('185057512345673'))->equals(new InsMatricule('885997512345663')));
    }
}
