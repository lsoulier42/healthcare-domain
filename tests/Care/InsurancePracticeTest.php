<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\InsurancePractice;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class InsurancePracticeTest extends TestCase
{
    public function testLiberalPractitionerPractice(): void
    {
        $practice = new InsurancePractice('12345678', '2', 'AM');

        self::assertSame('12345678', $practice->billingIdentifier);
        self::assertSame('2', $practice->specialtyCode);
        self::assertSame('AM', $practice->sectorCode);
    }

    public function testInputIsTrimmed(): void
    {
        $practice = new InsurancePractice(' 12345678 ', ' 2 ', ' AM ');

        self::assertSame('12345678', $practice->billingIdentifier);
        self::assertSame('2', $practice->specialtyCode);
        self::assertSame('AM', $practice->sectorCode);
    }

    public function testBlankValuesAreRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new InsurancePractice('', '2', 'AM');
    }

    public function testEqualityIsByAllValues(): void
    {
        $base = new InsurancePractice('12345678', '2', 'AM');

        self::assertTrue($base->equals(new InsurancePractice('12345678', '2', 'AM')));
        self::assertFalse($base->equals(new InsurancePractice('98765432', '2', 'AM')));
        self::assertFalse($base->equals(new InsurancePractice('12345678', '5', 'AM')));
        self::assertFalse($base->equals(new InsurancePractice('12345678', '2', 'AMC')));
    }
}