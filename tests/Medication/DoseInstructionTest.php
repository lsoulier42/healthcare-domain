<?php

declare(strict_types=1);

namespace Healthcare\Tests\Medication;

use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\Unit;
use Healthcare\Medication\ValueObject\AdministrationRouteCode;
use Healthcare\Medication\ValueObject\DoseInstruction;
use PHPUnit\Framework\TestCase;

final class DoseInstructionTest extends TestCase
{
    public function testTextOnlyInstruction(): void
    {
        $instruction = DoseInstruction::fromText('1 comprimé matin et soir pendant 7 jours');

        self::assertSame('1 comprimé matin et soir pendant 7 jours', $instruction->text);
        self::assertNull($instruction->quantity);
        self::assertNull($instruction->frequency);
        self::assertNull($instruction->duration);
        self::assertNull($instruction->route);
    }

    public function testStructuredInstruction(): void
    {
        $instruction = DoseInstruction::fromParts(
            '1 comprimé par jour',
            new Quantity('1', Unit::milligram()),
            '1 fois par jour',
            '7 jours',
            AdministrationRouteCode::fromEdqm('20053000', 'Oral use'),
        );

        self::assertSame('1', (string) $instruction->quantity?->value);
        self::assertSame('1 fois par jour', $instruction->frequency);
        self::assertSame('7 jours', $instruction->duration);
        self::assertNotNull($instruction->route);
    }

    public function testEmptyOptionalValuesBecomeNull(): void
    {
        $instruction = DoseInstruction::fromParts('1 comprimé', frequency: ' ', duration: ' ');

        self::assertNull($instruction->frequency);
        self::assertNull($instruction->duration);
    }

    public function testTextIsTrimmed(): void
    {
        self::assertSame('1 comprimé', DoseInstruction::fromText('  1 comprimé  ')->text);
    }

    public function testBlankTextIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);
        DoseInstruction::fromText('   ');
    }

    public function testEqualityRequiresAllFields(): void
    {
        $base = DoseInstruction::fromParts('1 comprimé', new Quantity('1', Unit::milligram()), 'matin');

        self::assertTrue($base->equals(
            DoseInstruction::fromParts('1 comprimé', new Quantity('1', Unit::milligram()), 'matin'),
        ));
        self::assertFalse($base->equals(
            DoseInstruction::fromParts('2 comprimés', new Quantity('1', Unit::milligram()), 'matin'),
        ));
        self::assertFalse($base->equals(
            DoseInstruction::fromParts('1 comprimé', new Quantity('2', Unit::milligram()), 'matin'),
        ));
        self::assertFalse($base->equals(
            DoseInstruction::fromParts('1 comprimé', new Quantity('1', Unit::milligram()), 'soir'),
        ));
    }
}
