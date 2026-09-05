<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\Service\InsiTraitsNormalizer;
use PHPUnit\Framework\TestCase;

final class InsiTraitsNormalizerTest extends TestCase
{
    public function testUppercases(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalize('dupont'));
    }

    public function testStripsDiacritics(): void
    {
        self::assertSame('GARCIA-HAMMADI', InsiTraitsNormalizer::normalize('García-Hämmadi'));
        self::assertSame('CELINE', InsiTraitsNormalizer::normalize('Céline'));
        self::assertSame('REMI', InsiTraitsNormalizer::normalize('Rémi'));
        self::assertSame('GARAUD', InsiTraitsNormalizer::normalize('GARAUD'));
    }

    public function testExpandsLigatures(): void
    {
        self::assertSame('OEUVRE', InsiTraitsNormalizer::normalize('Œuvre'));
        self::assertSame('CAESAR', InsiTraitsNormalizer::normalize('Cæsar'));
    }

    public function testKeepsAllowedSpecialCharacters(): void
    {
        self::assertSame("D'ARGENT", InsiTraitsNormalizer::normalize("d'argent"));
        self::assertSame('JEAN--CLAUDE', InsiTraitsNormalizer::normalize('Jean--Claude'));
        self::assertSame('JEAN CLAUDE', InsiTraitsNormalizer::normalize('jean claude'));
    }

    public function testStripsForbiddenCharacters(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalize('Du9pont!'));
        self::assertSame('MARTINPAUL', InsiTraitsNormalizer::normalize('Martin_Paul'));
    }

    public function testTrims(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalize('  dupont  '));
    }

    public function testEmptyAfterNormalizationIsInvalid(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValid(''));
        self::assertFalse(InsiTraitsNormalizer::isValid('   '));
        self::assertFalse(InsiTraitsNormalizer::isValid('!!!'));
    }

    public function testFirstCharacterMustNotBeHyphen(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValid('-DUPONT'));
    }

    public function testLeadingSpaceIsNormalizedAway(): void
    {
        self::assertSame('DUPONT', InsiTraitsNormalizer::normalize(' DUPONT'));
        self::assertTrue(InsiTraitsNormalizer::isValid(' DUPONT'));
    }

    public function testFirstCharacterMayBeApostrophe(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValid("D'ARC"));
    }

    public function testSpaceAndApostropheCannotBeDoubledOrCombined(): void
    {
        self::assertFalse(InsiTraitsNormalizer::isValid('JEAN  CLAUDE'));  // double space
        self::assertFalse(InsiTraitsNormalizer::isValid("JEAN''PIERRE")); // double apostrophe
        self::assertFalse(InsiTraitsNormalizer::isValid("JEAN 'PIERRE")); // space + apostrophe
        self::assertFalse(InsiTraitsNormalizer::isValid("JEAN' PIERRE")); // apostrophe + space
    }

    public function testDoubleHyphenIsAllowed(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValid('JEAN--CLAUDE'));
    }

    public function testValidValuesAreAccepted(): void
    {
        self::assertTrue(InsiTraitsNormalizer::isValid('GARCIA-HAMMADI'));
        self::assertTrue(InsiTraitsNormalizer::isValid("D'ARGENT"));
    }
}