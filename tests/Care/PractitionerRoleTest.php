<?php

declare(strict_types=1);

namespace Healthcare\Tests\Care;

use DateTimeImmutable;
use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Practitioner;
use Healthcare\Care\Entity\PractitionerRole;
use Healthcare\Care\ValueObject\ProfessionalTitle;
use Healthcare\Care\ValueObject\ProfessionCode;
use Healthcare\Care\ValueObject\SpecialtyCode;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\Exception\InvalidPeriod;
use Healthcare\Kernel\ValueObject\Period;
use Healthcare\Kernel\ValueObject\Rpps;
use PHPUnit\Framework\TestCase;

final class PractitionerRoleTest extends TestCase
{
    private function practitioner(): Practitioner
    {
        return new Practitioner(
            'p-1',
            new HumanName('Lovelace', ['Ada']),
            new Rpps('12345678901'),
            ProfessionalTitle::DOCTOR,
        );
    }

    public function testRoleIsRegisteredOnBothSidesAndCarriesCodedExerciseData(): void
    {
        $practitioner = $this->practitioner();
        $organization = new Organization('o-1', 'Clinique Exemple');
        $role = new PractitionerRole(
            'role-1',
            $practitioner,
            $organization,
            profession: ProfessionCode::fromTreG15('10', 'Médecin'),
            specialty: SpecialtyCode::fromTreR38('SM41', 'Pneumologie'),
        );

        self::assertSame($role, $practitioner->roles()[0]);
        self::assertSame($role, $organization->roles()[0]);
        self::assertNotNull($role->profession());
        self::assertNotNull($role->specialty());

        $profession = $role->profession();
        $specialty = $role->specialty();

        self::assertSame('10', $profession->coding->code);
        self::assertSame('Médecin', $profession->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_G15-ProfessionSante/FHIR/TRE-G15-ProfessionSante',
            (string) $profession->coding->system,
        );
        self::assertSame('SM41', $specialty->coding->code);
        self::assertSame('Pneumologie', $specialty->coding->display);
        self::assertSame(
            'https://mos.esante.gouv.fr/NOS/TRE_R38-SpecialiteOrdinale/FHIR/TRE-R38-SpecialiteOrdinale',
            (string) $specialty->coding->system,
        );
    }

    public function testRoleSupportsUnknownAndHistoricalCodes(): void
    {
        $role = new PractitionerRole(
            'role-1',
            $this->practitioner(),
            new Organization('o-1', 'Clinique Exemple'),
            profession: ProfessionCode::fromTreG15('SM999', 'Profession future'),
            specialty: SpecialtyCode::fromTreR38('SM999', 'Spécialité future'),
        );

        $profession = $role->profession();
        $specialty = $role->specialty();

        self::assertNotNull($profession);
        self::assertNotNull($specialty);
        self::assertSame('SM999', $profession->coding->code);
        self::assertSame('SM999', $specialty->coding->code);
    }

    public function testRoleAcceptsGenericValidityPeriod(): void
    {
        $role = new PractitionerRole(
            'role-1',
            $this->practitioner(),
            new Organization('o-1', 'Clinique Exemple'),
            validityPeriod: new Period(
                new DateTimeImmutable('2025-01-01'),
                new DateTimeImmutable('2025-12-31'),
            ),
        );

        self::assertNotNull($role->validityPeriod());
        self::assertSame('2025-01-01', $role->validityPeriod()->start?->format('Y-m-d'));
        self::assertSame('2025-12-31', $role->validityPeriod()->end?->format('Y-m-d'));
    }

    public function testRoleCannotEndBeforeItStarts(): void
    {
        $this->expectException(InvalidPeriod::class);
        new PractitionerRole(
            'role-1',
            $this->practitioner(),
            new Organization('o-1', 'Clinique Exemple'),
            validityPeriod: new Period(
                new DateTimeImmutable('2025-01-02'),
                new DateTimeImmutable('2025-01-01'),
            ),
        );
    }

    public function testRoleCanBeDeactivatedAndReactivated(): void
    {
        $role = new PractitionerRole('role-1', $this->practitioner(), new Organization('o-1', 'Clinique Exemple'));

        self::assertTrue($role->isActive());

        $role->deactivate();
        self::assertFalse($role->isActive());

        $role->activate();
        self::assertTrue($role->isActive());
    }
}
