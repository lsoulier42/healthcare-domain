<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\SpecialtyCode;
use PHPUnit\Framework\TestCase;

final class SpecialtyCodeTest extends TestCase
{
    public function testSpecialtyCodeCarriesTreR38CodeAndDisplay(): void
    {
        $specialty = SpecialtyCode::fromTreR38('SM41', 'Pneumologie');

        self::assertSame('SM41', $specialty->coding->code);
        self::assertSame('Pneumologie', $specialty->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            (string) $specialty->coding->system,
        );
    }

    public function testUnknownSpecialtyCodesRemainRepresentable(): void
    {
        $specialty = SpecialtyCode::fromTreR38('SM999');

        self::assertSame('SM999', $specialty->coding->code);
        self::assertNull($specialty->coding->display);
    }

    public function testEqualityIsSystemAndCodeBased(): void
    {
        $a = SpecialtyCode::fromTreR38('SM41', 'Pneumologie');
        $b = SpecialtyCode::fromTreR38('SM41');

        self::assertTrue($a->equals($b));
    }
}
