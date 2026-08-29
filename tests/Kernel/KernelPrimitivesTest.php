<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use DateTimeImmutable;
use Healthcare\Kernel\Exception\InvalidPeriod;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\DecimalString;
use Healthcare\Kernel\ValueObject\Period;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\QuantityComparator;
use Healthcare\Kernel\ValueObject\Ratio;
use Healthcare\Kernel\ValueObject\Unit;
use PHPUnit\Framework\TestCase;

final class KernelPrimitivesTest extends TestCase
{
    public function testPeriodAcceptsOpenBounds(): void
    {
        $open = new Period();
        $startOnly = new Period(new DateTimeImmutable('2025-01-01'));
        $endOnly = new Period(end: new DateTimeImmutable('2025-12-31'));

        self::assertNull($open->start);
        self::assertNull($open->end);
        self::assertSame('2025-01-01', $startOnly->start?->format('Y-m-d'));
        self::assertNull($startOnly->end);
        self::assertNull($endOnly->start);
        self::assertSame('2025-12-31', $endOnly->end?->format('Y-m-d'));
    }

    public function testPeriodRejectsEndBeforeStart(): void
    {
        $this->expectException(InvalidPeriod::class);
        new Period(new DateTimeImmutable('2025-01-02'), new DateTimeImmutable('2025-01-01'));
    }

    public function testPeriodAllowsEqualBounds(): void
    {
        $period = new Period(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-01-01'));

        self::assertNotNull($period->start);
        self::assertNotNull($period->end);
    }

    public function testPeriodEquality(): void
    {
        $a = new Period(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-12-31'));
        $b = new Period(new DateTimeImmutable('2025-01-01'), new DateTimeImmutable('2025-12-31'));
        $c = new Period(new DateTimeImmutable('2025-01-01'));

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testQuantityKeepsDecimalStringAndAcceptsZeroAndSignedValues(): void
    {
        $positive = new Quantity('0.5', Unit::milliliter());
        $zero = new Quantity('0', Unit::milliliter());
        $negative = new Quantity('-3.2', Unit::fromUcum('mmol/L'));

        self::assertSame('0.5', (string) $positive->value);
        self::assertSame('mL', $positive->unit->code());
        self::assertSame('0.5 mL', (string) $positive);
        self::assertSame('0', (string) $zero->value);
        self::assertSame('0 mL', (string) $zero);
        self::assertSame('-3.2', (string) $negative->value);
        self::assertSame('-3.2 mmol/L', (string) $negative);
    }

    public function testQuantityAcceptsScientificNotationPerFhirDecimalPattern(): void
    {
        $quantity = new Quantity('1.5e3', Unit::milligram());

        self::assertSame('1.5e3', (string) $quantity->value);
        self::assertSame('1.5e3 mg', (string) $quantity);
    }

    public function testQuantityRejectsNonFhirDecimalForms(): void
    {
        self::assertFalse(DecimalString::isValidValue('.5'));
        self::assertFalse(DecimalString::isValidValue('5.'));
        self::assertFalse(DecimalString::isValidValue('00.5'));
        self::assertFalse(DecimalString::isValidValue('1,5'));
        self::assertFalse(DecimalString::isValidValue('NaN'));
        self::assertFalse(DecimalString::isValidValue('INF'));
    }

    public function testQuantitySupportsComparisonModifiers(): void
    {
        $lessThan = new Quantity('5', Unit::fromUcum('ng/L'), QuantityComparator::LESS_THAN);

        self::assertSame(QuantityComparator::LESS_THAN, $lessThan->comparator);
        self::assertSame('< 5 ng/L', (string) $lessThan);
        self::assertSame('<', QuantityComparator::LESS_THAN->value);
        self::assertSame('>=', QuantityComparator::GREATER_THAN_OR_EQUAL->value);
    }

    public function testQuantityComparatorParticipatesInEquality(): void
    {
        $a = new Quantity('5', Unit::fromUcum('ng/L'), QuantityComparator::LESS_THAN);
        $b = new Quantity('5', Unit::fromUcum('ng/L'));
        $c = new Quantity('5', Unit::fromUcum('ng/L'), QuantityComparator::LESS_THAN);

        self::assertFalse($a->equals($b));
        self::assertTrue($a->equals($c));
    }

    public function testQuantityRejectsBlankAndNonNumeric(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Quantity('', Unit::milliliter());
    }

    public function testUnitIsUcumCoding(): void
    {
        self::assertSame('mg', Unit::milligram()->code());
        self::assertSame('ug', Unit::microgram()->code());
        self::assertSame('g', Unit::gram()->code());
        self::assertSame('mL', Unit::milliliter()->code());
        self::assertSame('L', Unit::liter()->code());
        self::assertSame('[iU]', Unit::internationalUnit()->code());
        self::assertSame('[iU]/mL', Unit::internationalUnitPerMilliliter()->code());
        self::assertSame('http://unitsofmeasure.org', (string) Unit::milligram()->coding->system);
    }

    public function testUnitSupportsArbitraryUcumCodes(): void
    {
        $unit = Unit::fromUcum('mmol/L', 'millimole per liter');

        self::assertSame('mmol/L', $unit->code());
        self::assertSame('millimole per liter', $unit->coding->display);
    }

    public function testQuantityEquality(): void
    {
        $a = new Quantity('500', Unit::milligram());
        $b = new Quantity('500', Unit::milligram());
        $c = new Quantity('500.0', Unit::milligram());
        $d = new Quantity('500', Unit::gram());

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertFalse($a->equals($d));
    }

    public function testRatioComposesTwoQuantities(): void
    {
        $ratio = new Ratio(
            new Quantity('500', Unit::milligram()),
            new Quantity('5', Unit::milliliter()),
        );

        self::assertSame('500 mg / 5 mL', (string) $ratio);
        self::assertTrue($ratio->equals(new Ratio(
            new Quantity('500', Unit::milligram()),
            new Quantity('5', Unit::milliliter()),
        )));
    }
}
