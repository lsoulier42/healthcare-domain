<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\Service\InsiDatamatrixPayload;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use Healthcare\Kernel\ValueObject\Oid;
use PHPUnit\Framework\TestCase;

final class InsiDatamatrixPayloadTest extends TestCase
{
    private const HEADER = 'IS010000000000000000000000';
    private const GS = "\x1D";

    public function testBuildsMinimalPayload(): void
    {
        $payload = InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'SARAH-LOU ANNA',
            'GARCIA-HAMMADI',
            AdministrativeGender::FEMALE,
            new Date('1977-01-21'),
        );

        $expected = self::HEADER
            . 'S1' . '277010115400329'
            . 'S2' . '1.2.250.1.213.1.4.8' . self::GS
            . 'S3' . 'SARAH-LOU ANNA' . self::GS
            . 'S4' . 'GARCIA-HAMMADI' . self::GS
            . 'S5' . 'F'
            . 'S6' . '21-01-1977';

        self::assertSame($expected, $payload);
    }

    public function testBuildsPayloadWithBirthPlace(): void
    {
        $payload = InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'SARAH-LOU ANNA',
            'GARCIA-HAMMADI',
            AdministrativeGender::FEMALE,
            new Date('1977-01-21'),
            new CogCode('01154'),
        );

        self::assertStringEndsWith('S6' . '21-01-1977' . 'S7' . '01154', $payload);
        // 26 (header) + S1(2+15) + S2(2+19+GS) + S3(2+14+GS)
        // + S4(2+14+GS) + S5(2+1) + S6(2+10) + S7(2+5) = 121
        self::assertSame(121, strlen($payload));
    }

    public function testNormalizesNamesToInsiProfile(): void
    {
        $payload = InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'Sarah-Lou Anna',
            'García-Hämmadi',
            AdministrativeGender::MALE,
            new Date('1977-01-21'),
        );

        self::assertStringContainsString('S4' . 'GARCIA-HAMMADI', $payload);
        self::assertStringContainsString('S5' . 'M', $payload);
    }

    public function testBirthDateIsDayMonthYear(): void
    {
        $payload = InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'SARAH',
            'GARCIA',
            AdministrativeGender::FEMALE,
            new Date('2000-12-31'),
        );

        self::assertStringContainsString('S6' . '31-12-2000', $payload);
    }

    public function testIndeterminateGenderIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'SARAH',
            'GARCIA',
            AdministrativeGender::UNKNOWN,
            new Date('2000-12-31'),
        );
    }

    public function testInvalidNameProfileIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            '-SARAH', // leading hyphen is not allowed after normalization
            'GARCIA',
            AdministrativeGender::FEMALE,
            new Date('2000-12-31'),
        );
    }

    public function testFixedLengthFieldsCarryNoSeparator(): void
    {
        $payload = InsiDatamatrixPayload::build(
            new InsMatricule('277010115400329'),
            new Oid('1.2.250.1.213.1.4.8'),
            'SARAH',
            'GARCIA',
            AdministrativeGender::FEMALE,
            new Date('2000-12-31'),
        );

        // S1 (fixed), S5 (fixed) and S6 (fixed, last) are followed directly
        // by the next identifier or terminate the message.
        self::assertDoesNotMatchRegularExpression('/S1\d{15}\x1D/', $payload);
        self::assertDoesNotMatchRegularExpression('/S5[FM]\x1D/', $payload);
        self::assertStringEndsWith('S6' . '31-12-2000', $payload);
    }
}