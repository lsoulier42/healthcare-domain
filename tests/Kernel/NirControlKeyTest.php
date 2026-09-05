<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Kernel\ValueObject\Nir;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NirControlKeyTest extends TestCase
{
    public function testRejectsLettersInTheControlKey(): void
    {
        // Synthetic base with expected control key 01; PHP casts "1A" to 1.
        $value = '18505751230431A';

        self::assertFalse(Nir::isValidValue($value));
        self::assertNull(Nir::tryFrom($value));
        self::assertFalse(Nir::isStructurallyValid($value));
        self::assertFalse(Nir::hasValidControlKey($value));

        $this->expectException(InvalidIdentifier::class);
        new Nir($value);
    }

    #[DataProvider('malformedValues')]
    public function testDirectKeyValidationRejectsMalformedInput(string $value): void
    {
        self::assertFalse(Nir::hasValidControlKey($value));
    }

    /** @return iterable<string, array{string}> */
    public static function malformedValues(): iterable
    {
        yield 'empty' => [''];
        yield 'base only' => ['1850575123043'];
        yield 'short key' => ['18505751230431'];
        yield 'extra digit' => ['1850575123043010'];
        yield 'letter key' => ['18505751230431B'];
        yield 'invalid base with matching arithmetic key' => ['000000000000097'];
        yield 'non-numeric base' => ['XXXXXXXXXXXXX97'];
        yield 'trailing newline' => ["18505751230431\n"];
    }

    public function testPreservesValidKeysAndBaseNirs(): void
    {
        foreach (['185057512304301', '185057512345673', '281102A12500964', '281102B12500991'] as $value) {
            self::assertTrue(Nir::hasValidControlKey($value));
            self::assertTrue(Nir::isValidValue($value));
            self::assertSame($value, (string) new Nir($value));
            self::assertSame($value, Nir::tryFrom($value)?->value);
        }

        self::assertSame('1850575123043', (string) new Nir('1850575123043'));
    }
}
