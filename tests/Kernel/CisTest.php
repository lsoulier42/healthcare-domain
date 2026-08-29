<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Kernel\ValueObject\Cis;
use PHPUnit\Framework\TestCase;

final class CisTest extends TestCase
{
    /**
     * Source: glossaire BDPM (ANSM) / ministère de la Santé — le CIS est
     * un code numérique de 8 chiffres identifiant le médicament quelle
     * que soit sa présentation. Aucune clé de contrôle documentée.
     */
    public function testCisIsEightDigits(): void
    {
        $cis = new Cis('60000000');

        self::assertSame('60000000', (string) $cis);
        self::assertTrue(Cis::isValidValue('60000000'));
        self::assertNull(Cis::tryFrom('1234567'));
        self::assertNull(Cis::tryFrom('123456789'));
    }

    public function testCisNormalizesSeparators(): void
    {
        self::assertSame('60000000', (string) new Cis('60 000 000'));
    }

    public function testInvalidCisIsRejected(): void
    {
        $this->expectException(InvalidIdentifier::class);
        new Cis('1234');
    }
}
