<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\IdentityAttribute;
use Healthcare\Identity\ValueObject\IdentityAttributes;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class IdentityAttributesTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $attributes = IdentityAttributes::empty();

        self::assertTrue($attributes->isEmpty());
        self::assertFalse($attributes->requiresProvisionalStatus());
        self::assertFalse($attributes->blocksInsiLookup());
    }

    public function testDeduplicatesInput(): void
    {
        $attributes = new IdentityAttributes([
            IdentityAttribute::HOMONYM,
            IdentityAttribute::HOMONYM,
        ]);

        self::assertCount(1, $attributes->attributes);
        self::assertTrue($attributes->has(IdentityAttribute::HOMONYM));
    }

    public function testDoubtfulRequiresProvisionalAndBlocksInsi(): void
    {
        $attributes = new IdentityAttributes([IdentityAttribute::DOUBTFUL]);

        self::assertTrue($attributes->requiresProvisionalStatus());
        self::assertTrue($attributes->blocksInsiLookup());
    }

    public function testFictitiousRequiresProvisionalAndBlocksInsi(): void
    {
        $attributes = new IdentityAttributes([IdentityAttribute::FICTITIOUS]);

        self::assertTrue($attributes->requiresProvisionalStatus());
        self::assertTrue($attributes->blocksInsiLookup());
    }

    public function testHomonymDoesNotRestrictStatus(): void
    {
        $attributes = new IdentityAttributes([IdentityAttribute::HOMONYM]);

        self::assertFalse($attributes->requiresProvisionalStatus());
        self::assertFalse($attributes->blocksInsiLookup());
    }

    public function testFictitiousAndDoubtfulTogetherAreRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        new IdentityAttributes([IdentityAttribute::FICTITIOUS, IdentityAttribute::DOUBTFUL]);
    }

    public function testHomonymCombinesWithDoubtful(): void
    {
        $attributes = new IdentityAttributes([
            IdentityAttribute::HOMONYM,
            IdentityAttribute::DOUBTFUL,
        ]);

        self::assertTrue($attributes->has(IdentityAttribute::HOMONYM));
        self::assertTrue($attributes->requiresProvisionalStatus());
    }

    public function testEqualityIsOrderIndependent(): void
    {
        $a = new IdentityAttributes([IdentityAttribute::DOUBTFUL, IdentityAttribute::HOMONYM]);
        $b = new IdentityAttributes([IdentityAttribute::HOMONYM, IdentityAttribute::DOUBTFUL]);

        self::assertTrue($a->equals($b));
    }
}
