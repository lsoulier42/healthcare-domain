<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class DateTest extends TestCase
{
    public function testValidCalendarDateIsAccepted(): void
    {
        $date = new Date('1985-05-17');

        self::assertSame('1985-05-17', (string) $date);
        self::assertTrue(Date::isValidValue('2024-02-29')); // leap day
    }

    public function testInputIsTrimmed(): void
    {
        self::assertSame('1985-05-17', (string) new Date(' 1985-05-17 '));
    }

    public function testStrictCalendarValidation(): void
    {
        self::assertFalse(Date::isValidValue('2023-02-31')); // not a real date
        self::assertFalse(Date::isValidValue('2023-13-01')); // month 13
        self::assertFalse(Date::isValidValue('2023-00-10')); // month 0
        self::assertFalse(Date::isValidValue('2023-01-00')); // day 0
        self::assertFalse(Date::isValidValue('2023-02-29')); // not a leap year
        self::assertNull(Date::tryFrom('2023-02-31'));
    }

    public function testMalformedValuesAreRejected(): void
    {
        self::assertFalse(Date::isValidValue(''));
        self::assertFalse(Date::isValidValue('17/05/1985'));
        self::assertFalse(Date::isValidValue('1985-5-17')); // not zero-padded
        self::assertFalse(Date::isValidValue('19850517'));
        self::assertFalse(Date::isValidValue('1985-05-17 10:30'));
        self::assertNull(Date::tryFrom('1985-05-17 10:30'));
    }

    public function testInvalidDateIsRejectedAtConstruction(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Date('2023-02-31');
    }

    public function testEqualityIsByCalendarValue(): void
    {
        self::assertTrue((new Date('1985-05-17'))->equals(new Date('1985-05-17')));
        self::assertTrue((new Date('1985-05-17'))->equals(new Date(' 1985-05-17 ')));
        self::assertFalse((new Date('1985-05-17'))->equals(new Date('1985-05-18')));
    }
}
