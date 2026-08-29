<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use DateTimeImmutable;
use Healthcare\Care\ValueObject\OrganizationIdentity;
use Healthcare\Care\ValueObject\PractitionerRole;
use Healthcare\Care\ValueObject\ProfessionCode;
use Healthcare\Care\ValueObject\SavoirFaireCode;
use Healthcare\Kernel\Exception\InvalidPeriod;
use Healthcare\Kernel\ValueObject\Period;
use Healthcare\Kernel\ValueObject\Siret;
use PHPUnit\Framework\TestCase;

final class PractitionerRoleTest extends TestCase
{
    public function testRoleCarriesProfessionAndSavoirFaire(): void
    {
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [
                SavoirFaireCode::fromTreR38('SM41', 'Pneumologie'),
            ],
        );

        self::assertSame('10', $role->profession->coding->code);
        self::assertSame('Médecin', $role->profession->coding->display);
        self::assertCount(1, $role->savoirFaire);
        self::assertSame('SM41', $role->savoirFaire[0]->coding->code);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            (string) $role->savoirFaire[0]->coding->system,
        );
    }

    public function testRoleSupportsExclusiveCompetence(): void
    {
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [SavoirFaireCode::fromTreR40('CEX01', 'Compétence exclusive')],
        );

        self::assertSame('CEX01', $role->savoirFaire[0]->coding->code);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R40-CompetenceExclusive/FHIR/TRE-R40-CompetenceExclusive',
            (string) $role->savoirFaire[0]->coding->system,
        );
    }

    public function testRoleSupportsUnknownAndHistoricalCodes(): void
    {
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('SM999', 'Profession future'),
            savoirFaire: [SavoirFaireCode::fromTreR38('SM999', 'Spécialité future')],
        );

        self::assertSame('SM999', $role->profession->coding->code);
        self::assertSame('SM999', $role->savoirFaire[0]->coding->code);
    }

    public function testRoleAcceptsOptionalOrganizationIdentity(): void
    {
        $organization = new OrganizationIdentity(
            name: 'Clinique Exemple',
            siret: new Siret('73282932000074'),
        );
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            organization: $organization,
        );

        self::assertNotNull($role->organization);
        self::assertSame('Clinique Exemple', $role->organization->name);
    }

    public function testRoleMayExistWithoutOrganization(): void
    {
        $role = new PractitionerRole(ProfessionCode::fromTreG15('10', 'Médecin'));

        self::assertNull($role->organization);
    }

    public function testRoleAcceptsGenericValidityPeriod(): void
    {
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            validityPeriod: new Period(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-12-31'),
            ),
        );

        self::assertNotNull($role->validityPeriod);
        self::assertSame('2025-01-01', $role->validityPeriod->start?->format('Y-m-d'));
        self::assertSame('2025-12-31', $role->validityPeriod->end?->format('Y-m-d'));
    }

    public function testRoleCannotEndBeforeItStarts(): void
    {
        $this->expectException(InvalidPeriod::class);

        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            validityPeriod: new Period(
                new DateTimeImmutable('2025-01-02'),
                new DateTimeImmutable('2025-01-01'),
            ),
        );
        self::assertInstanceOf(PractitionerRole::class, $role);
    }

    public function testRoleDeduplicatesSavoirFaire(): void
    {
        $role = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [
                SavoirFaireCode::fromTreR38('SM41'),
                SavoirFaireCode::fromTreR38('SM41', 'Pneumologie'),
            ],
        );

        self::assertCount(1, $role->savoirFaire);
    }

    public function testEqualityIsOrderIndependentOverSavoirFaire(): void
    {
        $a = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [
                SavoirFaireCode::fromTreR38('SM41'),
                SavoirFaireCode::fromTreR40('CEX01'),
            ],
        );
        $b = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [
                SavoirFaireCode::fromTreR40('CEX01'),
                SavoirFaireCode::fromTreR38('SM41'),
            ],
        );

        self::assertTrue($a->equals($b));
    }

    public function testEqualityDistinguishesProfession(): void
    {
        $a = new PractitionerRole(ProfessionCode::fromTreG15('10', 'Médecin'));
        $b = new PractitionerRole(ProfessionCode::fromTreG15('11', 'Chirurgien'));

        self::assertFalse($a->equals($b));
    }

    public function testEqualityDistinguishesOrganization(): void
    {
        $a = new PractitionerRole(ProfessionCode::fromTreG15('10', 'Médecin'));
        $b = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            organization: new OrganizationIdentity('Clinique Exemple'),
        );

        self::assertFalse($a->equals($b));
    }

    public function testEqualityDistinguishesSavoirFaire(): void
    {
        $a = new PractitionerRole(
            ProfessionCode::fromTreG15('10', 'Médecin'),
            savoirFaire: [SavoirFaireCode::fromTreR38('SM41')],
        );
        $b = new PractitionerRole(ProfessionCode::fromTreG15('10', 'Médecin'));

        self::assertFalse($a->equals($b));
    }
}
