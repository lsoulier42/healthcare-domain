<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Kernel\ValueObject\Oid;
use PHPUnit\Framework\TestCase;

final class InsAssigningAuthorityTest extends TestCase
{
    public function testOfficialFactoriesExposeTheirOids(): void
    {
        self::assertSame('1.2.250.1.213.1.4.8', (string) InsAssigningAuthority::nir()->oid);
        self::assertSame('1.2.250.1.213.1.4.9', (string) InsAssigningAuthority::nia()->oid);
        self::assertSame('1.2.250.1.213.1.4.10', (string) InsAssigningAuthority::nirTest()->oid);
        self::assertSame('1.2.250.1.213.1.4.11', (string) InsAssigningAuthority::nirDemo()->oid);
    }

    public function testUnknownOrFutureOidRemainsRepresentable(): void
    {
        $authority = new InsAssigningAuthority(new Oid('1.2.250.1.999.1.4.42'));

        self::assertSame('1.2.250.1.999.1.4.42', (string) $authority->oid);
    }

    public function testEqualityIsBasedOnTheOid(): void
    {
        self::assertTrue(
            InsAssigningAuthority::nir()->equals(new InsAssigningAuthority(new Oid('1.2.250.1.213.1.4.8'))),
        );
        self::assertFalse(InsAssigningAuthority::nir()->equals(InsAssigningAuthority::nia()));
        self::assertFalse(InsAssigningAuthority::nir()->equals(InsAssigningAuthority::nirTest()));
    }
}
