<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Adeli;
use Healthcare\Kernel\ValueObject\Atc;
use Healthcare\Kernel\ValueObject\Cip13;
use Healthcare\Kernel\ValueObject\Cip7;
use Healthcare\Kernel\ValueObject\Finess;
use Healthcare\Kernel\ValueObject\Nir;
use Healthcare\Kernel\ValueObject\Rpps;
use Healthcare\Kernel\ValueObject\Siren;
use Healthcare\Kernel\ValueObject\Siret;
use Healthcare\Kernel\ValueObject\Ucd;
use PHPUnit\Framework\TestCase;

final class IdentifierTest extends TestCase
{
    public function testAdeliNormalizesSeparatorsAndValidatesItsFormat(): void
    {
        $adeli = new Adeli('12 345 6789');

        self::assertSame('123456789', (string) $adeli);
        self::assertTrue(Adeli::isValidValue('12-345-6789'));
        self::assertNull(Adeli::tryFrom('12345678'));
    }

    public function testRppsRemainsStructurallyValidated(): void
    {
        $rpps = new Rpps('12345678901');
        self::assertSame('12345678901', (string) $rpps);
        self::assertTrue(Rpps::isValidValue('12345678901'));
        self::assertNull(Rpps::tryFrom('invalid'));
    }

    public function testNirNormalizesSeparatorsAndExposesComponents(): void
    {
        $nir = new Nir('1 85 05 75 123 456');

        self::assertSame('1850575123456', (string) $nir);
        self::assertSame('1', $nir->sex());
        self::assertSame('05', $nir->birthMonth());
        self::assertSame('75123', $nir->birthInseeCode());
    }

    public function testNirWithInvalidControlKeyIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Nir('185057512345600');
    }

    public function testSirenAndSiretCheckTheirChecksums(): void
    {
        self::assertSame('732829320', (string) new Siren('732829320'));
        self::assertSame('73282932000074', (string) new Siret('73282932000074'));

        $this->expectException(InvalidValueObject::class);
        new Siret('73282932000075');
    }

    public function testFinessChecksItsWeightedChecksum(): void
    {
        self::assertSame('010000014', (string) new Finess('010000014'));

        $this->expectException(InvalidValueObject::class);
        new Finess('010000015');
    }

    public function testCipCodesCheckTheirModuloTenDigit(): void
    {
        self::assertSame('3400931234562', (string) new Cip13('3400931234562'));
        self::assertSame('3400934', (string) new Cip7('3400934'));

        $this->expectException(InvalidValueObject::class);
        new Cip13('3400931234561');
    }

    public function testInvalidIdentifierIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Finess('123');
    }

    public function testAtcIsUppercased(): void
    {
        self::assertSame('A01BC02', (string) new Atc('a01bc02'));
    }

    public function testNirWith14CharactersIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Nir('18505751234567');
    }

    public function testNirAcceptsInseeSpecialMonthCodes(): void
    {
        self::assertSame('1852075123456', (string) new Nir('1852075123456')); // 20: unknown month
        self::assertSame('1853375123456', (string) new Nir('1853375123456')); // 33: pseudo-fictive (march)
        self::assertSame('1859975123456', (string) new Nir('1859975123456')); // 99: fictive (foreign birth)
        self::assertTrue(Nir::isValidValue('185207512345622')); // 15 digits with valid key
    }

    public function testNirRejectsInvalidMonthCodes(): void
    {
        self::assertFalse(Nir::isValidValue('1850075123456')); // 00
        self::assertFalse(Nir::isValidValue('1851475123456')); // 14
        self::assertFalse(Nir::isValidValue('1851975123456')); // 19
        self::assertFalse(Nir::isValidValue('1854375123456')); // 43
        self::assertFalse(Nir::isValidValue('1854975123456')); // 49
    }

    public function testNirValidatesStandardControlKey(): void
    {
        self::assertTrue(Nir::isValidValue('185057512345673'));
        self::assertFalse(Nir::isValidValue('185057512345672'));
    }

    public function testNirValidatesCorsicanControlKeyPerInseeRule(): void
    {
        self::assertSame('281102A12500964', (string) new Nir('281102A12500964'));
        self::assertSame('281102B12500991', (string) new Nir('281102B12500991'));
        self::assertTrue(Nir::isValidValue('281102A12500964'));
        self::assertFalse(Nir::isValidValue('281102A12500965')); // wrong key
        self::assertFalse(Nir::isValidValue('281102A12500937')); // key computed without the Corsica adjustment
    }

    public function testNirAcceptsProvisionalSexCodes(): void
    {
        self::assertSame('885997512345663', (string) new Nir('885997512345663')); // sex 8, born abroad
        self::assertSame('781102A12500908', (string) new Nir('781102A12500908')); // sex 7, Corsica
    }

    public function testTryFromReturnsNullOnInvalidValue(): void
    {
        self::assertNull(Rpps::tryFrom('invalid'));
        self::assertNull(Adeli::tryFrom('12345678'));
        self::assertNull(Finess::tryFrom('123'));
        self::assertNull(Siren::tryFrom('123'));
        self::assertNull(Siret::tryFrom('123'));
        self::assertNull(Cip7::tryFrom('123'));
        self::assertNull(Cip13::tryFrom('123'));
        self::assertNull(Ucd::tryFrom('123'));
        self::assertNull(Atc::tryFrom('123'));
        self::assertNull(Nir::tryFrom('123'));
    }

    public function testTryFromReturnsInstanceOnValidValue(): void
    {
        self::assertInstanceOf(Rpps::class, Rpps::tryFrom('12345678901'));
        self::assertInstanceOf(Adeli::class, Adeli::tryFrom('123456789'));
        self::assertInstanceOf(Finess::class, Finess::tryFrom('010000014'));
        self::assertInstanceOf(Siren::class, Siren::tryFrom('732829320'));
        self::assertInstanceOf(Siret::class, Siret::tryFrom('73282932000074'));
        self::assertInstanceOf(Cip7::class, Cip7::tryFrom('3400934'));
        self::assertInstanceOf(Cip13::class, Cip13::tryFrom('3400931234562'));
        self::assertInstanceOf(Ucd::class, Ucd::tryFrom('1234567'));
        self::assertInstanceOf(Atc::class, Atc::tryFrom('A01BC02'));
        self::assertInstanceOf(Nir::class, Nir::tryFrom('1850575123456'));
    }
}
