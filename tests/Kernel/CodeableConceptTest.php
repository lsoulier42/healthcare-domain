<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Coding;
use PHPUnit\Framework\TestCase;

final class CodeableConceptTest extends TestCase
{
    public function testAcceptsMultipleCodingsAndOptionalText(): void
    {
        $concept = new CodeableConcept(
            [
                new Coding(new CodeSystem('urn:example:lab'), 'GLUC', 'Glucose local'),
                new Coding(CodeSystem::loinc(), '2345-7', 'Glucose [Mass/volume] in Serum'),
            ],
            'Glucose',
        );

        self::assertCount(2, $concept->codings);
        self::assertSame('Glucose', $concept->text);
        self::assertSame('Glucose', (string) $concept);
    }

    public function testRequiresAtLeastOneCoding(): void
    {
        $this->expectException(InvalidValueObject::class);
        new CodeableConcept([]);
    }

    public function testTextOnlyIsAccepted(): void
    {
        $concept = new CodeableConcept([], 'Résultat ininterprétable');

        self::assertSame([], $concept->codings);
        self::assertSame('Résultat ininterprétable', $concept->text);
        self::assertSame('Résultat ininterprétable', (string) $concept);
    }

    public function testCodingsAreDeduplicatedByStrictEquality(): void
    {
        $concept = new CodeableConcept([
            new Coding(CodeSystem::loinc(), '2345-7'),
            new Coding(CodeSystem::loinc(), '2345-7'),
            new Coding(CodeSystem::loinc(), '2345-7', version: '2.72'),
        ]);

        self::assertCount(2, $concept->codings);
    }

    public function testEqualsIsSoundWithDuplicateCodings(): void
    {
        $a = new CodeableConcept([
            new Coding(CodeSystem::loinc(), 'X'),
            new Coding(CodeSystem::loinc(), 'X'),
        ]);
        $b = new CodeableConcept([
            new Coding(CodeSystem::loinc(), 'X'),
            new Coding(CodeSystem::loinc(), 'Y'),
        ]);

        self::assertFalse($a->equals($b));
    }

    public function testBlankTextNormalizesToNull(): void
    {
        $concept = new CodeableConcept(
            [new Coding(CodeSystem::loinc(), '2345-7')],
            '  ',
        );

        self::assertNull($concept->text);
        self::assertSame('http://loinc.org|2345-7', (string) $concept);
    }

    public function testEqualsComparesCodingsAndText(): void
    {
        $a = new CodeableConcept(
            [new Coding(CodeSystem::loinc(), '2345-7')],
            'Glucose',
        );
        $b = new CodeableConcept(
            [new Coding(CodeSystem::loinc(), '2345-7')],
            'Glucose',
        );
        $c = new CodeableConcept(
            [new Coding(CodeSystem::loinc(), '2345-7')],
        );
        $d = new CodeableConcept(
            [new Coding(CodeSystem::loinc(), '2346-5')],
        );

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertFalse($a->equals($d));
    }

    public function testEqualsIgnoresCodingsOrder(): void
    {
        $a = new CodeableConcept([
            new Coding(CodeSystem::loinc(), '2345-7'),
            new Coding(new CodeSystem('urn:example:lab'), 'GLUC'),
        ]);
        $b = new CodeableConcept([
            new Coding(new CodeSystem('urn:example:lab'), 'GLUC'),
            new Coding(CodeSystem::loinc(), '2345-7'),
        ]);

        self::assertTrue($a->equals($b));
    }

    public function testHasCodingUsesStrictEquality(): void
    {
        $concept = new CodeableConcept([
            new Coding(CodeSystem::loinc(), '2345-7', version: '2.72'),
        ]);

        self::assertTrue($concept->hasCoding(new Coding(CodeSystem::loinc(), '2345-7', version: '2.72')));
        self::assertFalse($concept->hasCoding(new Coding(CodeSystem::loinc(), '2345-7')));
        self::assertFalse($concept->hasCoding(new Coding(CodeSystem::snomedCt(), '2345-7')));
    }

    public function testHasCodingInMatchesSystemAndCode(): void
    {
        $concept = new CodeableConcept([
            new Coding(CodeSystem::loinc(), '2345-7'),
        ]);

        self::assertTrue($concept->hasCodingIn(CodeSystem::loinc(), '2345-7'));
        self::assertFalse($concept->hasCodingIn(CodeSystem::loinc(), '2346-5'));
        self::assertFalse($concept->hasCodingIn(CodeSystem::snomedCt(), '2345-7'));
    }
}
