<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\DecimalString;
use PHPUnit\Framework\TestCase;

final class DecimalStringTest extends TestCase
{
    public function testPreservesOriginalRepresentation(): void
    {
        self::assertSame('500', (string) new DecimalString('500'));
        self::assertSame('500.0', (string) new DecimalString('500.0'));
        self::assertSame('-3.20', (string) new DecimalString('-3.20'));
        self::assertSame('1.5e3', (string) new DecimalString('1.5e3'));
        self::assertSame('1.5E-3', (string) new DecimalString('1.5E-3'));
    }

    public function testPrecisionIsSignificantInEquality(): void
    {
        $a = new DecimalString('500');
        $b = new DecimalString('500.0');

        self::assertFalse($a->equals($b));
        self::assertTrue($a->equals(new DecimalString('500')));
    }

    public function testZeroClassificationIsExact(): void
    {
        foreach (['0', '0.0', '0.00', '-0', '-0.0', '0e3', '0e-3', '0E10', '0.000e5'] as $zero) {
            $decimal = new DecimalString($zero);
            self::assertTrue($decimal->isZero(), "expected $zero to be zero");
            self::assertFalse($decimal->isPositive(), "expected $zero not positive");
            self::assertFalse($decimal->isNegative(), "expected $zero not negative");
        }
    }

    public function testPositiveClassification(): void
    {
        foreach (['1', '0.1', '1.5e3', '5e-2', '500.0'] as $value) {
            $decimal = new DecimalString($value);
            self::assertTrue($decimal->isPositive(), "expected $value positive");
            self::assertFalse($decimal->isZero());
            self::assertFalse($decimal->isNegative());
        }
    }

    public function testNegativeClassification(): void
    {
        foreach (['-1', '-0.1', '-1.5e3', '-5e-2'] as $value) {
            $decimal = new DecimalString($value);
            self::assertTrue($decimal->isNegative(), "expected $value negative");
            self::assertFalse($decimal->isZero());
            self::assertFalse($decimal->isPositive());
        }
    }

    public function testRejectsInvalidForms(): void
    {
        foreach (['', '.5', '5.', '00.5', '1,5', 'NaN', 'INF', '--1', '1e', 'e3', '1.2.3'] as $value) {
            self::assertFalse(
                DecimalString::isValidValue($value),
                "expected $value to be rejected",
            );

            try {
                new DecimalString($value);
                self::fail("expected $value to throw");
            } catch (InvalidValueObject) {
                // expected
            }
        }
    }
}
