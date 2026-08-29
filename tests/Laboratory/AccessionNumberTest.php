<?php

declare(strict_types=1);

namespace Healthcare\Tests\Laboratory;

use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Laboratory\ValueObject\AccessionNumber;
use PHPUnit\Framework\TestCase;

final class AccessionNumberTest extends TestCase
{
    public function testAnyNonBlankAccessionNumberIsAccepted(): void
    {
        $accession = new AccessionNumber('LAB-2025-0001');

        self::assertSame('LAB-2025-0001', (string) $accession);
        self::assertTrue($accession->equals(new AccessionNumber('LAB-2025-0001')));
        self::assertFalse($accession->equals(new AccessionNumber('LAB-2025-0002')));
    }

    public function testTryFromIsFailSoft(): void
    {
        self::assertNull(AccessionNumber::tryFrom('  '));
        self::assertSame('12345', (string) AccessionNumber::tryFrom('12345'));
    }

    public function testBlankAccessionNumberIsRejected(): void
    {
        $this->expectException(InvalidIdentifier::class);
        new AccessionNumber('  ');
    }
}
