<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class HumanNameTest extends TestCase
{
    public function testHumanNameBuildsFromFamilyAndGivenNames(): void
    {
        $name = new HumanName('LOVELACE', ['Ada', 'Byron']);

        self::assertSame('LOVELACE', $name->familyName);
        self::assertSame(['Ada', 'Byron'], $name->givenNames);
        self::assertSame('Ada', $name->firstGivenName());
        self::assertSame('Ada Byron LOVELACE', (string) $name);
    }

    public function testUsualNameIsOptionalAndNormalized(): void
    {
        self::assertNull((new HumanName('KING'))->usualName);
        self::assertSame('Augusta', (new HumanName('KING', ['Augusta'], 'Augusta'))->usualName);
        self::assertNull((new HumanName('KING', usualName: '  '))->usualName);
    }

    public function testBlankGivenNamesAreSkipped(): void
    {
        $name = new HumanName('LOVELACE', ['Ada', '', '  ']);

        self::assertSame(['Ada'], $name->givenNames);
    }

    public function testBlankFamilyNameIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new HumanName('  ');
    }

    public function testEquality(): void
    {
        $a = new HumanName('LOVELACE', ['Ada'], 'Ada');
        $b = new HumanName('LOVELACE', ['Ada'], 'Ada');
        $c = new HumanName('LOVELACE', ['Ada']);

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testUsualGivenName(): void
    {
        $name = new HumanName('LOVELACE', ['Ada'], 'Lovelace', 'Ada');

        self::assertSame('Ada', $name->usualGivenName);
    }

    public function testUsualGivenNameIsOptionalAndTrimmed(): void
    {
        $name = new HumanName('LOVELACE', ['Ada']);

        self::assertNull($name->usualGivenName);
        self::assertNull((new HumanName('LOVELACE', ['Ada'], usualGivenName: '  '))->usualGivenName);
        self::assertSame('Ada', (new HumanName('LOVELACE', ['Ada'], usualGivenName: ' Ada '))->usualGivenName);
    }

    public function testEqualityIncludesUsualGivenName(): void
    {
        $withUsual = new HumanName('LOVELACE', ['Ada'], usualGivenName: 'Ada');

        self::assertFalse($withUsual->equals(new HumanName('LOVELACE', ['Ada'])));
        self::assertTrue($withUsual->equals(new HumanName('LOVELACE', ['Ada'], usualGivenName: 'Ada')));
    }
}
