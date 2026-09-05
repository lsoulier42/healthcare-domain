<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\AmoPracticeContext;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class AmoPracticeContextTest extends TestCase
{
    public function testLiberalPractitionerContext(): void
    {
        $context = new AmoPracticeContext('12345678', 'AM', '2');

        self::assertSame('12345678', $context->billingIdentifier);
        self::assertSame('AM', $context->sectorCode);
        self::assertSame('2', $context->specialtyCode);
        self::assertNull($context->gipProfessionCode);
    }

    public function testSpecialtyCodeIsOptional(): void
    {
        $context = new AmoPracticeContext('12345678', 'AM');

        self::assertNull($context->specialtyCode);
    }

    public function testPscMultiProfessionContext(): void
    {
        $context = new AmoPracticeContext('12345678', 'AM', '2', '42');

        self::assertSame('42', $context->gipProfessionCode);
    }

    public function testBlankOptionalValuesBecomeNull(): void
    {
        $context = new AmoPracticeContext('12345678', 'AM', ' ', ' ');

        self::assertNull($context->specialtyCode);
        self::assertNull($context->gipProfessionCode);
    }

    public function testInputIsTrimmed(): void
    {
        $context = new AmoPracticeContext(' 12345678 ', ' AM ');

        self::assertSame('12345678', $context->billingIdentifier);
        self::assertSame('AM', $context->sectorCode);
    }

    public function testBlankRequiredValuesAreRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new AmoPracticeContext('', 'AM');
    }

    public function testBlankSectorIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new AmoPracticeContext('12345678', '');
    }

    public function testEqualityIsByAllValues(): void
    {
        $base = new AmoPracticeContext('12345678', 'AM', '2', '42');

        self::assertTrue($base->equals(new AmoPracticeContext('12345678', 'AM', '2', '42')));
        self::assertFalse($base->equals(new AmoPracticeContext('98765432', 'AM', '2', '42')));
        self::assertFalse($base->equals(new AmoPracticeContext('12345678', 'AM', '5', '42')));
        self::assertFalse($base->equals(new AmoPracticeContext('12345678', 'AM', '2', '43')));
        self::assertFalse($base->equals(new AmoPracticeContext('12345678', 'AM')));
    }
}
