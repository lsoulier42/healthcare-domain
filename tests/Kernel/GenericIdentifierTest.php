<?php

declare(strict_types=1);

namespace Healthcare\Tests\Kernel;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Identifier;
use PHPUnit\Framework\TestCase;

final class GenericIdentifierTest extends TestCase
{
    public function testIdentifierBindsSystemAndValue(): void
    {
        $identifier = new Identifier(new CodeSystem('urn:example:lab'), 'S-0001');

        self::assertSame('S-0001', $identifier->value);
        self::assertSame('urn:example:lab', (string) $identifier->system);
        self::assertSame('urn:example:lab|S-0001', (string) $identifier);
    }

    public function testEqualityRequiresSameSystemAndValue(): void
    {
        $a = new Identifier(new CodeSystem('urn:a'), '1');
        $b = new Identifier(new CodeSystem('urn:a'), '1');
        $c = new Identifier(new CodeSystem('urn:b'), '1');
        $d = new Identifier(new CodeSystem('urn:a'), '2');

        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
        self::assertFalse($a->equals($d));
    }

    public function testBlankValueIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        new Identifier(new CodeSystem('urn:a'), '  ');
    }
}
