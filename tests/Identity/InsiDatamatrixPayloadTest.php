<?php

declare(strict_types=1);

namespace Healthcare\Tests\Identity;

use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\Service\InsiDatamatrixPayload;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\Exception\InvalidDomainState;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class InsiDatamatrixPayloadTest extends TestCase
{
    private const HEADER = 'IS010000000000000000000000';
    private const GS = "\x1D";

    /**
     * @param ?list<string> $givenNames
     */
    private function identity(
        ?string $familyName = null,
        ?array $givenNames = null,
        AdministrativeGender $gender = AdministrativeGender::FEMALE,
        ?string $oid = null,
    ): PatientIdentity {
        $familyName ??= 'LOVELACE';
        $givenNames ??= ['Ada'];

        $traits = new StrictIdentityTraits(
            birthFamilyName: $familyName,
            firstBirthGivenName: $givenNames[0],
            birthGivenNames: $givenNames,
            birthDate: new Date('1815-12-10'),
            gender: $gender,
            birthPlace: new CogCode('99100'),
        );

        $insIdentifier = new InsIdentifier(
            new InsMatricule('185057512345673'),
            new InsAssigningAuthority(new \Healthcare\Kernel\ValueObject\Oid($oid ?? '1.2.250.1.213.1.4.8')),
        );

        return PatientIdentity::qualified($traits, $insIdentifier);
    }

    public function testBuildsPayloadFromQualifiedIdentity(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity($this->identity());

        $expected = self::HEADER
            . 'S1' . '185057512345673'                 // fixed 15: no GS
            . 'S2' . '1.2.250.1.213.1.4.8' . self::GS  // 19 < 20: GS
            . 'S3' . 'ADA' . self::GS                  // < 100: GS
            . 'S4' . 'LOVELACE' . self::GS             // < 100: GS
            . 'S5' . 'F'                               // fixed 1: no GS
            . 'S6' . '10-12-1815'                      // fixed 10, JJ-MM-AAAA
            . 'S7' . '99100';                          // fixed 5, last

        self::assertSame($expected, $payload);
    }

    public function testOidAtMaximumLengthCarriesNoSeparator(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(oid: '1.2.250.1.213.1.4.99'), // 20 characters
        );

        self::assertStringContainsString('S2' . '1.2.250.1.213.1.4.99' . 'S3', $payload);
    }

    public function testOidTooLongIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(oid: '1.2.250.1.213.1.4.100'), // 21 characters
        );
    }

    public function testNameAtMaximumLengthCarriesNoSeparator(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(familyName: str_repeat('A', 100)),
        );

        self::assertStringContainsString('S4' . str_repeat('A', 100) . 'S5', $payload);
    }

    public function testGivenNameListIsSpaceJoined(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(givenNames: ['Sarah-Lou', 'Anna']),
        );

        self::assertStringContainsString('S3' . 'SARAH-LOU ANNA' . self::GS, $payload);
    }

    public function testNamesAreNormalized(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(familyName: 'García-Hämmadi', givenNames: ['Sarah-Lou']),
        );

        self::assertStringContainsString('S4' . 'GARCIA-HAMMADI', $payload);
        self::assertStringContainsString('S3' . 'SARAH-LOU', $payload);
    }

    public function testIndeterminateGenderIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(gender: AdministrativeGender::UNKNOWN),
        );
    }

    public function testNonQualifiedIdentityIsRejected(): void
    {
        $this->expectException(InvalidDomainState::class);

        $traits = new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
        $insIdentifier = new InsIdentifier(
            new InsMatricule('185057512345673'),
            InsAssigningAuthority::nir(),
        );

        InsiDatamatrixPayload::fromQualifiedIdentity(PatientIdentity::recovered($traits, $insIdentifier));
    }

    public function testNameWithForbiddenCharacterIsRejected(): void
    {
        $this->expectException(InvalidValueObject::class);

        InsiDatamatrixPayload::fromQualifiedIdentity(
            $this->identity(familyName: 'LOVELACE2'),
        );
    }

    public function testLengthIsDeterministic(): void
    {
        $payload = InsiDatamatrixPayload::fromQualifiedIdentity($this->identity());

        // 26 (header) + S1(2+15) + S2(2+19+GS) + S3(2+3+GS)
        // + S4(2+8+GS) + S5(2+1) + S6(2+10) + S7(2+5) = 104
        self::assertSame(104, strlen($payload));
    }
}
