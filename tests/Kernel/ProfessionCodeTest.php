<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Care\ValueObject\ProfessionCode;
use PHPUnit\Framework\TestCase;

final class ProfessionCodeTest extends TestCase
{
    public function testProfessionCodeCarriesTreG15CodeAndDisplay(): void
    {
        $profession = ProfessionCode::fromTreG15('10', 'Médecin');

        self::assertSame('10', $profession->coding->code);
        self::assertSame('Médecin', $profession->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_G15-ProfessionSante/FHIR/TRE-G15-ProfessionSante',
            (string) $profession->coding->system,
        );
    }

    public function testUnknownProfessionCodesRemainRepresentable(): void
    {
        $profession = ProfessionCode::fromTreG15('99', 'Profession future');

        self::assertSame('99', $profession->coding->code);
        self::assertSame('Profession future', $profession->coding->display);
    }
}
