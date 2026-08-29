<?php

declare(strict_types=1);

namespace Healthcare\Tests\Geographic;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Kernel\Exception\InvalidIdentifier;
use PHPUnit\Framework\TestCase;

final class CogCodeTest extends TestCase
{
    /**
     * Source: référentiel INS / guide d'implémentation INS v3.0 (ANS) —
     * le code du lieu de naissance est le code officiel géographique :
     * commune (2 chars département + 3 chars commune), 99 + code pays
     * pour l'étranger, 99999 si inconnu.
     */
    public function testCommuneCodesAreNormalizedAndAccepted(): void
    {
        self::assertSame('75056', (new CogCode(' 75056 '))->value); // Paris
        self::assertSame('2A004', (new CogCode('2a004'))->value); // Ajaccio
        self::assertSame('97105', (new CogCode('97105'))->value); // Basse-Terre
        self::assertTrue(CogCode::isValidValue('01154')); // commune de l'Ain
    }

    public function testForeignCountryCodesUse99Prefix(): void
    {
        self::assertSame('99100', (new CogCode('99100'))->value); // Royaume-Uni
        self::assertTrue(CogCode::isValidValue('99134')); // Espagne
        self::assertTrue(CogCode::isValidValue('99345')); // pays fictif dans la plage COG
    }

    public function testUnknownBirthPlaceUses99999(): void
    {
        self::assertSame('99999', (new CogCode('99999'))->value);
        self::assertTrue(CogCode::isValidValue('99999'));
    }

    public function testDepartmentOnlyCodesAreRejected(): void
    {
        self::assertFalse(CogCode::isValidValue('75'));
        self::assertFalse(CogCode::isValidValue('2A'));
        self::assertFalse(CogCode::isValidValue('971'));
        self::assertFalse(CogCode::isValidValue('99'));
    }

    public function testInvalidCodesAreRejected(): void
    {
        self::assertFalse(CogCode::isValidValue('00'));
        self::assertFalse(CogCode::isValidValue('2C'));
        self::assertFalse(CogCode::isValidValue('750'));
        self::assertFalse(CogCode::isValidValue('750561'));
        self::assertFalse(CogCode::isValidValue('9A100'));
        self::assertFalse(CogCode::isValidValue('75ABC'));
        self::assertFalse(CogCode::isValidValue('9910A'));

        $this->expectException(InvalidIdentifier::class);
        new CogCode('75');
    }

    public function testTryFromIsFailSoft(): void
    {
        self::assertNull(CogCode::tryFrom('75'));
        self::assertInstanceOf(CogCode::class, CogCode::tryFrom('75056'));
    }
}
