<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\IdentityAttribute;
use PHPUnit\Framework\TestCase;

final class IdentityAttributeTest extends TestCase
{
    public function testLabels(): void
    {
        self::assertSame('Identité homonyme', IdentityAttribute::HOMONYM->label());
        self::assertSame('Identité douteuse', IdentityAttribute::DOUBTFUL->label());
        self::assertSame('Identité fictive', IdentityAttribute::FICTITIOUS->label());
    }

    public function testFictitiousAndDoubtfulCannotBeCombined(): void
    {
        self::assertFalse(IdentityAttribute::DOUBTFUL->combinesWith(IdentityAttribute::FICTITIOUS));
        self::assertFalse(IdentityAttribute::FICTITIOUS->combinesWith(IdentityAttribute::DOUBTFUL));
    }

    public function testHomonymCombinesWithEverything(): void
    {
        self::assertTrue(IdentityAttribute::HOMONYM->combinesWith(IdentityAttribute::DOUBTFUL));
        self::assertTrue(IdentityAttribute::HOMONYM->combinesWith(IdentityAttribute::FICTITIOUS));
    }

    public function testOnlyDoubtfulAndFictitiousRestrictToProvisional(): void
    {
        self::assertTrue(IdentityAttribute::DOUBTFUL->restrictsToProvisionalStatus());
        self::assertTrue(IdentityAttribute::FICTITIOUS->restrictsToProvisionalStatus());
        self::assertFalse(IdentityAttribute::HOMONYM->restrictsToProvisionalStatus());
    }
}
