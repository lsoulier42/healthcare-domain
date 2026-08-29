<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use Healthcare\Care\ValueObject\PractitionerIdentity;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Rpps;
use PHPUnit\Framework\TestCase;

final class PractitionerReferenceTest extends TestCase
{
    public function testBlankIdIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new PractitionerReference('   ');
    }

    public function testIdentitySnapshotIsOptional(): void
    {
        $reference = new PractitionerReference('practitioner-1');

        self::assertSame('practitioner-1', $reference->id);
        self::assertNull($reference->identity);
    }

    public function testCarriesOptionalIdentitySnapshot(): void
    {
        $reference = new PractitionerReference(
            id: 'practitioner-1',
            identity: new PractitionerIdentity(
                new HumanName('Curie', ['Marie']),
                rpps: new Rpps('12345678901'),
            ),
        );

        self::assertNotNull($reference->identity);
        self::assertSame('Curie', $reference->identity->name->familyName);
    }

    public function testEqualityIsBasedOnIdOnly(): void
    {
        $plain = new PractitionerReference('practitioner-1');
        $withIdentity = new PractitionerReference(
            id: 'practitioner-1',
            identity: new PractitionerIdentity(new HumanName('Curie', ['Marie'])),
        );

        self::assertTrue($plain->equals($withIdentity));
    }

    public function testEqualityDistinguishesIds(): void
    {
        $a = new PractitionerReference('practitioner-1');
        $b = new PractitionerReference('practitioner-2');

        self::assertFalse($a->equals($b));
    }
}
