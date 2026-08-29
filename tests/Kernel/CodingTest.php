<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;
use PHPUnit\Framework\TestCase;

final class CodingTest extends TestCase
{
    public function testValidConstructionWithOptionalDisplayAndVersion(): void
    {
        $concept = new Coding(CodeSystem::loinc(), '718-7', 'Hemoglobin', '2.72');

        self::assertSame('718-7', $concept->code);
        self::assertSame('Hemoglobin', $concept->display);
        self::assertSame('2.72', $concept->version);
        self::assertSame('http://loinc.org|718-7', (string) $concept);
    }

    public function testBlankCodeIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Coding(CodeSystem::loinc(), '');
    }

    public function testBlankDisplayAndVersionNormalizeToNull(): void
    {
        $concept = new Coding(CodeSystem::loinc(), '718-7', '  ', '');

        self::assertNull($concept->display);
        self::assertNull($concept->version);
    }

    public function testEqualityIgnoresDisplay(): void
    {
        $a = new Coding(CodeSystem::loinc(), '718-7', 'Hemoglobin');
        $b = new Coding(CodeSystem::loinc(), '718-7');

        self::assertTrue($a->equals($b));
    }

    public function testEqualityDistinguishesSystemAndCode(): void
    {
        $a = new Coding(CodeSystem::loinc(), '718-7');
        $b = new Coding(CodeSystem::snomedCt(), '718-7');
        $c = new Coding(CodeSystem::loinc(), '719-5');

        self::assertFalse($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testEqualityIsStrictOnVersionAndTransitive(): void
    {
        $versioned = new Coding(CodeSystem::loinc(), '718-7', version: '2.72');
        $otherVersion = new Coding(CodeSystem::loinc(), '718-7', version: '2.73');
        $unversioned = new Coding(CodeSystem::loinc(), '718-7');

        self::assertFalse($versioned->equals($unversioned));
        self::assertFalse($unversioned->equals($otherVersion));
        self::assertFalse($versioned->equals($otherVersion));

        $same = new Coding(CodeSystem::loinc(), '718-7', version: '2.72');
        self::assertTrue($versioned->equals($same));
    }

    public function testSameCodeAsIgnoresVersion(): void
    {
        $a = new Coding(CodeSystem::loinc(), '718-7', version: '2.72');
        $b = new Coding(CodeSystem::loinc(), '718-7');
        $c = new Coding(CodeSystem::loinc(), '718-7', version: '2.73');
        $d = new Coding(CodeSystem::loinc(), '719-5', version: '2.72');

        self::assertTrue($a->sameCodeAs($b));
        self::assertTrue($b->sameCodeAs($c));
        self::assertFalse($a->sameCodeAs($d));
        self::assertFalse($a->sameCodeAs(new Coding(CodeSystem::snomedCt(), '718-7')));
    }

    public function testKnownCodeSystemFactories(): void
    {
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_G15-ProfessionSante/FHIR/TRE-G15-ProfessionSante',
            (string) CodeSystem::ansTreG15(),
        );
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            (string) CodeSystem::ansTreR38(),
        );
        self::assertSame('http://loinc.org', (string) CodeSystem::loinc());
        self::assertSame('http://snomed.info/sct', (string) CodeSystem::snomedCt());
        self::assertSame('https://standardterms.edqm.eu', (string) CodeSystem::edqm());
        self::assertSame('http://unitsofmeasure.org', (string) CodeSystem::ucum());
    }

    public function testCodeSystemDoesNotHardCodeKnownSystems(): void
    {
        $future = new CodeSystem('https://future-terminology.example.org/cs');

        self::assertSame('https://future-terminology.example.org/cs', (string) $future);
    }

    public function testBlankCodeSystemIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new CodeSystem('  ');
    }
}
