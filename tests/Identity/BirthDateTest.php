<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\BirthDate;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class BirthDateTest extends TestCase
{
    public function testExactBirthDate(): void
    {
        $birthDate = BirthDate::exact(new Date('1977-01-21'));

        self::assertFalse($birthDate->isEstimated());
        self::assertSame('1977-01-21', (string) $birthDate->date);
    }

    public function testEstimatedBirthDate(): void
    {
        $birthDate = BirthDate::estimated(new Date('1977-12-31'));

        self::assertTrue($birthDate->isEstimated());
    }

    public function testCompletedDayUsesFirstOfMonthAndIsFlagged(): void
    {
        $birthDate = BirthDate::fromPartial(1977, 1, null);

        self::assertSame('1977-01-01', (string) $birthDate->date);
        self::assertTrue($birthDate->isEstimated());
    }

    public function testCompletedMonthUsesJanuaryAndIsFlagged(): void
    {
        $birthDate = BirthDate::fromPartial(1977, null, 21);

        self::assertSame('1977-01-21', (string) $birthDate->date);
        self::assertTrue($birthDate->isEstimated());
    }

    public function testUnkownDayAndMonthUseDecember31AndAreFlagged(): void
    {
        $birthDate = BirthDate::fromPartial(1977, null, null);

        self::assertSame('1977-12-31', (string) $birthDate->date);
        self::assertTrue($birthDate->isEstimated());
    }

    public function testCompletePartialIsExact(): void
    {
        $birthDate = BirthDate::fromPartial(1977, 1, 21);

        self::assertSame('1977-01-21', (string) $birthDate->date);
        self::assertFalse($birthDate->isEstimated());
    }

    public function testEqualityRequiresDateAndMarker(): void
    {
        self::assertTrue(
            BirthDate::exact(new Date('1977-01-21'))->equals(BirthDate::exact(new Date('1977-01-21'))),
        );
        self::assertFalse(
            BirthDate::exact(new Date('1977-01-21'))->equals(BirthDate::estimated(new Date('1977-01-21'))),
        );
    }
}
