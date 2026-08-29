<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use PHPUnit\Framework\TestCase;

final class InsIdentifierTest extends TestCase
{
    public function testComposesMatriculeAndAuthority(): void
    {
        $identifier = new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );

        self::assertSame('185057512345673', (string) $identifier->matricule);
        self::assertSame('1.2.250.1.213.1.4.8', (string) $identifier->authority->oid);
    }

    public function testEqualityRequiresMatriculeAndAuthority(): void
    {
        $base = new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );

        self::assertTrue($base->equals(new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        )));

        self::assertFalse($base->equals(new InsIdentifier(
            new InsMatricule('885997512345663'),
            InsAssigningAuthority::nir(),
        )));
    }

    public function testDifferentAuthorityWithSameMatriculeIsNotEqual(): void
    {
        $nir = new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );
        $nia = new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nia(),
        );

        self::assertFalse($nir->equals($nia));
    }
}
