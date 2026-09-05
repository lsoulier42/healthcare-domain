<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\IdentificationEvidence;
use Healthcare\Kernel\Exception\InvalidValueObject;
use PHPUnit\Framework\TestCase;

final class IdentificationEvidenceTest extends TestCase
{
    public function testHighConfidenceFactories(): void
    {
        self::assertTrue(IdentificationEvidence::nationalIdentityCard()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::CNI,
            IdentificationEvidence::nationalIdentityCard()->type,
        );

        self::assertTrue(IdentificationEvidence::passport()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::PASSPORT,
            IdentificationEvidence::passport()->type,
        );

        self::assertTrue(IdentificationEvidence::residencePermit()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::RESIDENCE_PERMIT,
            IdentificationEvidence::residencePermit()->type,
        );

        self::assertTrue(IdentificationEvidence::electronicIdentification()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::EIDAS,
            IdentificationEvidence::electronicIdentification()->type,
        );

        self::assertTrue(IdentificationEvidence::trustedThirdParty()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::TRUSTED_THIRD_PARTY,
            IdentificationEvidence::trustedThirdParty()->type,
        );

        self::assertTrue(IdentificationEvidence::appliCarteVitale()->isHighConfidence());
        self::assertSame(
            IdentificationEvidence::APPLI_CARTE_VITALE,
            IdentificationEvidence::appliCarteVitale()->type,
        );
    }

    public function testCustomTypeAndConfidence(): void
    {
        $evidence = IdentificationEvidence::fromType('student_card', false);

        self::assertSame('student_card', $evidence->type);
        self::assertFalse($evidence->isHighConfidence());
    }

    public function testTypeIsTrimmed(): void
    {
        $evidence = IdentificationEvidence::fromType(' cni ', true);

        self::assertSame('cni', $evidence->type);
    }

    public function testBlankTypeIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        IdentificationEvidence::fromType('', true);
    }

    public function testEqualityRequiresTypeAndConfidence(): void
    {
        self::assertTrue(
            IdentificationEvidence::passport()->equals(IdentificationEvidence::passport()),
        );
        self::assertFalse(
            IdentificationEvidence::passport()->equals(IdentificationEvidence::nationalIdentityCard()),
        );
        self::assertFalse(
            IdentificationEvidence::fromType('cni', false)->equals(IdentificationEvidence::nationalIdentityCard()),
        );
    }
}
