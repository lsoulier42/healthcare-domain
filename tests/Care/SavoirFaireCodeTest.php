<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\SavoirFaireCode;
use PHPUnit\Framework\TestCase;

final class SavoirFaireCodeTest extends TestCase
{
    public function testFromTreR38CarriesOrdinalSpecialty(): void
    {
        $code = SavoirFaireCode::fromTreR38('SM41', 'Pneumologie');

        self::assertSame('SM41', $code->coding->code);
        self::assertSame('Pneumologie', $code->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            (string) $code->coding->system,
        );
    }

    public function testFromTreR40CarriesExclusiveCompetence(): void
    {
        $code = SavoirFaireCode::fromTreR40('CEX01', 'Compétence exclusive');

        self::assertSame('CEX01', $code->coding->code);
        self::assertSame('Compétence exclusive', $code->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R40-CompetenceExclusive/FHIR/TRE-R40-CompetenceExclusive',
            (string) $code->coding->system,
        );
    }

    public function testTreR38AndTreR40AreNotInterchangeable(): void
    {
        $ordinal = SavoirFaireCode::fromTreR38('SM41');
        $exclusive = SavoirFaireCode::fromTreR40('SM41');

        self::assertFalse($ordinal->equals($exclusive));
        self::assertFalse($ordinal->sameCodeAs($exclusive));
    }

    public function testUnknownCodesRemainRepresentable(): void
    {
        $code = new SavoirFaireCode(new \Healthcare\Kernel\ValueObject\Coding(
            \Healthcare\Kernel\ValueObject\CodeSystem::ansTreR40(),
            'CEX999',
        ));

        self::assertSame('CEX999', $code->coding->code);
        self::assertNull($code->coding->display);
    }

    public function testEqualityIsSystemCodeAndVersionBased(): void
    {
        $a = SavoirFaireCode::fromTreR40('CEX01', 'Compétence exclusive', '2025');
        $b = SavoirFaireCode::fromTreR40('CEX01');

        self::assertFalse($a->equals($b));
        self::assertTrue($a->sameCodeAs($b));
    }
}
