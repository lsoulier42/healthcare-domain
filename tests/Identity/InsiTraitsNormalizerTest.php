<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\Service\InsiTraitProfile;
use Healthcare\Identity\Service\InsiTraitsNormalizer;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class InsiTraitsNormalizerTest extends TestCase
{
    public function testUppercases(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalize('dupont', InsiTraitProfile::FAMILY_NAME));
    }

    public function testStripsDiacritics(): void
    {
        self::assertSame(
            'GARCIA-HAMMADI',
            InsiTraitsNormalizer::normalizeBirthFamilyName('García-Hämmadi'),
        );
        self::assertSame('CELINE', InsiTraitsNormalizer::normalizeGivenName('Céline'));
        self::assertSame('REMI', InsiTraitsNormalizer::normalizeGivenName('Rémi'));
    }

    public function testExpandsLigatures(): void
    {
        self::assertSame('OEUVRE', InsiTraitsNormalizer::normalizeBirthFamilyName('Œuvre'));
        self::assertSame('CAESAR', InsiTraitsNormalizer::normalizeBirthFamilyName('Cæsar'));
    }

    public function testKeepsAllowedSpecialCharacters(): void
    {
        self::assertSame(
            "D'ARGENT",
            InsiTraitsNormalizer::normalizeBirthFamilyName("d'argent"),
        );
        self::assertSame('GARCIA--HAMMADI', InsiTraitsNormalizer::normalizeBirthFamilyName('Garcia--Hammadi'));
        self::assertSame('JEAN CLAUDE', InsiTraitsNormalizer::normalizeGivenName('Jean Claude'));
    }

    public function testFamilyNameAllowsDoubleHyphen(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValidBirthFamilyName('GARCIA--HAMMADI'));
    }

    public function testGivenNameRejectsDoubleHyphen(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValidGivenName('JEAN--CLAUDE'));
    }

    public function testGivenNameLastCharacterMustNotBeHyphenOrApostrophe(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValidGivenName('JEAN-'));
        self::assertFalse(InsiTraitsNormalizer::isValidGivenName("JEAN'"));
        self::assertTrue(InsiTraitsNormalizer::isValidGivenName('JEAN'));
    }

    public function testCharactersWithoutDefinedEquivalenceAreRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        InsiTraitsNormalizer::normalizeGivenName('Du9pont');
    }

    public function testUnderscoreIsRejectedNotSilentlyRemoved(): void
    {
        $this->expectException(InvalidValueObject::class);
        InsiTraitsNormalizer::normalizeBirthFamilyName('Martin_Paul');
    }

    public function testIsValidReturnsFalseForInvalidChars(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValidBirthFamilyName('DUPONT!'));
        self::assertFalse(InsiTraitsNormalizer::isValidDatamatrixName('SARAH123'));
    }

    public function testTrims(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalizeBirthFamilyName('  dupont  '));
    }

    public function testBlankIsInvalid(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValidBirthFamilyName(''));
        self::assertFalse(InsiTraitsNormalizer::isValidGivenName('   '));
    }

    public function testFirstCharacterMustNotBeHyphen(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValidBirthFamilyName('-DUPONT'));
        self::assertFalse(InsiTraitsNormalizer::isValidGivenName('-JEAN'));
    }

    public function testFirstCharacterMayBeApostrophe(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValidBirthFamilyName("D'ARC"));
    }

    public function testSpaceAndApostropheCannotBeDoubledOrCombined(): void
    {
        foreach (
            [
                InsiTraitsNormalizer::isValidBirthFamilyName('JEAN  CLAUDE'),
                InsiTraitsNormalizer::isValidBirthFamilyName("JEAN''PIERRE"),
                InsiTraitsNormalizer::isValidBirthFamilyName("JEAN 'PIERRE"),
                InsiTraitsNormalizer::isValidBirthFamilyName("JEAN' PIERRE"),
            ] as $result
        ) {
            self::assertFalse($result);
        }
    }

    public function testDatamatrixProfileMatchesFamilyNameLexicalRules(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValidDatamatrixName('GARCIA--HAMMADI'));
        self::assertTrue(InsiTraitsNormalizer::isValidDatamatrixName('jean')); // uppercased by normalization
        // The datamatrix profile has no last-character rule (unlike GIVEN_NAME).
        self::assertTrue(InsiTraitsNormalizer::isValidDatamatrixName('JEAN-'));
        self::assertFalse(InsiTraitsNormalizer::isValidDatamatrixName(''));
    }
}
