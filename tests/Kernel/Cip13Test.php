<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Kernel\ValueObject\Cip13;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class Cip13Test extends TestCase
{
    public function testUsesTheGs1CheckDigitInsteadOfLuhn(): void
    {
        // Synthetic payload: the GS1 weighted sum of its first 12 digits is 78.
        // The check digit is therefore 2, whereas Luhn would produce 0.
        self::assertTrue(Cip13::isValidValue('3400931234562'));
        self::assertFalse(Cip13::isValidValue('3400931234560'));
    }

    public function testNormalizesSeparatorsAndSupportsTryFrom(): void
    {
        $code = new Cip13(' 3400-9-3123456-2 ');

        self::assertSame('3400931234562', (string) $code);
        self::assertTrue($code->equals(new Cip13('3400931234562')));
        self::assertEquals($code, Cip13::tryFrom('3400 9 3123456 2'));
        self::assertTrue(Cip13::isValidValue('3400931234500')); // Synthetic, zero check digit.
        self::assertTrue(Cip13::isValidValue('3400931234579')); // Synthetic, check digit 9.
    }

    #[DataProvider('invalidCodes')]
    public function testRejectsMalformedCodesThroughEveryEntryPoint(string $value): void
    {
        self::assertFalse(Cip13::isValidValue($value));
        self::assertNull(Cip13::tryFrom($value));

        $this->expectException(InvalidIdentifier::class);
        new Cip13($value);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCodes(): iterable
    {
        yield 'Luhn key' => ['3400931234560'];
        yield 'wrong GS1 key' => ['3400931234561'];
        yield 'missing key' => ['340093123456'];
        yield 'extra digit' => ['03400931234562'];
        yield 'empty' => [''];
    }
}
