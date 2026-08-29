<?php

declare(strict_types=1);

namespace Healthcare\Tests\Clinical;

use DateTimeImmutable;
use Healthcare\Care\ValueObject\OrganizationIdentity;
use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerIdentity;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\Entity\ClinicalDocument;
use Healthcare\Clinical\Entity\DiagnosticReport;
use Healthcare\Clinical\Entity\Encounter;
use Healthcare\Clinical\Entity\Observation;
use Healthcare\Clinical\ValueObject\ReferenceRange;
use Healthcare\Clinical\Entity\ServiceRequest;
use Healthcare\Clinical\Entity\Specimen;
use Healthcare\Clinical\ValueObject\DiagnosticReportStatus;
use Healthcare\Clinical\ValueObject\DocumentContent;
use Healthcare\Clinical\ValueObject\IntegerValue;
use Healthcare\Clinical\ValueObject\ObservationCode;
use Healthcare\Clinical\ValueObject\ObservationStatus;
use Healthcare\Clinical\ValueObject\QuantityValue;
use Healthcare\Clinical\ValueObject\ServiceRequestStatus;
use Healthcare\Clinical\ValueObject\SpecimenTypeCode;
use Healthcare\Clinical\ValueObject\TextValue;
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\Exception\InvalidValueObject;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Coding;
use Healthcare\Kernel\ValueObject\Date;
use Healthcare\Kernel\ValueObject\Identifier;
use Healthcare\Kernel\ValueObject\Period;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\Unit;
use PHPUnit\Framework\TestCase;

final class ClinicalScenarioTest extends TestCase
{
    private function patient(): PatientReference
    {
        $traits = new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );

        return new PatientReference(
            id: 'patient-1',
            identity: PatientIdentity::provisional($traits),
        );
    }

    private function requester(): PractitionerReference
    {
        return new PractitionerReference(
            id: 'practitioner-1',
            identity: new PractitionerIdentity(new HumanName('Curie', ['Marie'])),
        );
    }

    public function testLaboratoryStyleScenarioComposes(): void
    {
        $patient = $this->patient();
        $requester = $this->requester();
        $lab = new OrganizationReference(
            id: 'organization-1',
            identity: new OrganizationIdentity('Laboratoire Exemple'),
        );

        $encounter = new Encounter(
            'enc-1',
            $patient,
            new Period(new DateTimeImmutable('2025-03-01 09:00')),
            type: new CodeableConcept([new Coding(new CodeSystem('urn:example:encounter-types'), 'consultation')]),
            organization: $lab,
        );

        $request = new ServiceRequest(
            'sr-1',
            $patient,
            new CodeableConcept([new Coding(CodeSystem::loinc(), '58410-2', 'CBC panel')]),
            ServiceRequestStatus::ACTIVE,
            requester: $requester,
            performerOrganization: $lab,
            encounter: $encounter,
            authoredAt: new DateTimeImmutable('2025-03-01 09:30'),
        );

        $specimen = new Specimen(
            'spec-1',
            patient: $patient,
            type: SpecimenTypeCode::fromSnomedCt('119297000', 'Blood specimen'),
            collectedAt: new DateTimeImmutable('2025-03-01 10:00'),
        );
        $specimen->addIdentifier(new Identifier(new CodeSystem('urn:example:specimen'), 'S-0001'));

        $observation = new Observation(
            'obs-1',
            $patient,
            ObservationCode::fromLoinc('718-7', 'Hemoglobin [Mass/volume] in Blood'),
            ObservationStatus::FINAL,
            new QuantityValue(new Quantity('140', Unit::fromUcum('g/L'))),
            effective: new DateTimeImmutable('2025-03-01 10:00'),
            specimen: $specimen,
        );
        $observation->addReferenceRange(new ReferenceRange(
            low: new Quantity('120', Unit::fromUcum('g/L')),
            high: new Quantity('160', Unit::fromUcum('g/L')),
        ));

        $report = new DiagnosticReport(
            'rep-1',
            $patient,
            new CodeableConcept([new Coding(CodeSystem::loinc(), '58410-2', 'CBC panel')]),
            DiagnosticReportStatus::FINAL,
            request: $request,
            issuedAt: new DateTimeImmutable('2025-03-01 15:00'),
            performer: $requester,
            performerOrganization: $lab,
            conclusion: 'No significant abnormality.',
        );
        $report->addResult($observation);
        $report->addSpecimen($specimen);

        self::assertSame($request, $report->request());
        self::assertCount(1, $report->results());
        self::assertSame('obs-1', $report->results()[0]->id());
        self::assertSame($specimen, $report->results()[0]->specimen());
        self::assertCount(1, $report->specimens());
        self::assertSame($specimen, $report->specimens()[0]);

        $value = $report->results()[0]->value();
        self::assertInstanceOf(QuantityValue::class, $value);
        self::assertSame('140 g/L', (string) $value->quantity);
    }

    public function testObservationCodeCarriesLocalAndLoincCodings(): void
    {
        $code = ObservationCode::fromCodings(
            [
                new Coding(new CodeSystem('urn:example:lab'), 'HB', 'Hémoglobine local'),
                new Coding(CodeSystem::loinc(), '718-7', 'Hemoglobin [Mass/volume] in Blood'),
            ],
            'Hémoglobine',
        );

        self::assertTrue($code->concept->hasCodingIn(CodeSystem::loinc(), '718-7'));
        self::assertTrue($code->concept->hasCodingIn(new CodeSystem('urn:example:lab'), 'HB'));
        self::assertSame('Hémoglobine', (string) $code);
    }

    public function testObservationSupportsTypedValues(): void
    {
        $patient = $this->patient();

        $textObservation = new Observation(
            'obs-t',
            $patient,
            ObservationCode::fromLoinc('11526-1'),
            ObservationStatus::PRELIMINARY,
            new TextValue('Clumped'),
        );

        self::assertInstanceOf(TextValue::class, $textObservation->value());
        self::assertSame('Clumped', $textObservation->value()->text);

        $integerObservation = new Observation(
            'obs-i',
            $patient,
            ObservationCode::fromLoinc('11156-4'),
            ObservationStatus::FINAL,
            new IntegerValue(5),
        );

        self::assertInstanceOf(IntegerValue::class, $integerObservation->value());
        self::assertSame(5, $integerObservation->value()->value);
    }

    public function testObservationEffectiveIsASingleChoice(): void
    {
        $patient = $this->patient();
        $at = new DateTimeImmutable('2025-03-01 10:00');
        $period = new Period(new DateTimeImmutable('2025-03-01 08:00'), new DateTimeImmutable('2025-03-02 08:00'));

        $pointObservation = new Observation(
            'obs-t1',
            $patient,
            ObservationCode::fromLoinc('11526-1'),
            ObservationStatus::FINAL,
            effective: $at,
        );
        self::assertSame($at, $pointObservation->effectiveAt());
        self::assertNull($pointObservation->effectivePeriod());

        $periodObservation = new Observation(
            'obs-t2',
            $patient,
            ObservationCode::fromLoinc('11526-1'),
            ObservationStatus::FINAL,
            effective: $period,
        );
        self::assertSame($period, $periodObservation->effectivePeriod());
        self::assertNull($periodObservation->effectiveAt());

        $periodObservation->changeEffective($at);
        self::assertSame($at, $periodObservation->effectiveAt());
        self::assertNull($periodObservation->effectivePeriod());

        $periodObservation->changeEffective(null);
        self::assertNull($periodObservation->effectiveAt());
        self::assertNull($periodObservation->effectivePeriod());
    }

    public function testReferenceRangeRequiresLowHighOrText(): void
    {
        $this->expectException(InvalidValueObject::class);
        new ReferenceRange();
    }

    public function testReferenceRangeAcceptsTextOnly(): void
    {
        $range = new ReferenceRange(text: 'Négatif');

        self::assertNull($range->low);
        self::assertNull($range->high);
        self::assertSame('Négatif', $range->text);
    }

    public function testClinicalDocumentCarriesNeutralContent(): void
    {
        $patient = $this->patient();
        $author = $this->requester();

        $document = new ClinicalDocument(
            'doc-1',
            $patient,
            new CodeableConcept([new Coding(CodeSystem::loinc(), '11502-2', 'Laboratory report')]),
            $author,
            new DateTimeImmutable('2025-03-01 15:05'),
            new DocumentContent(text: 'Hemoglobin: 140 g/L'),
            title: 'Hémogramme',
        );

        self::assertSame('Hémogramme', $document->title());
        self::assertSame('Hemoglobin: 140 g/L', $document->content()?->text);
        self::assertSame($author, $document->author());
        self::assertSame($patient, $document->patient());
    }

    public function testQualifiedIdentityScenarioUsesCompleteInsIdentifier(): void
    {
        $traits = new StrictIdentityTraits(
            birthFamilyName: 'TURING',
            firstBirthGivenName: 'Alan',
            birthGivenNames: ['Alan', 'Mathison'],
            birthDate: new Date('1912-06-23'),
            gender: AdministrativeGender::MALE,
            birthPlace: new CogCode('99132'), // synthetic COG birthplace code
        );

        $ins = new InsIdentifier(
            new InsMatricule('281102A12500964'), // synthetic matricule
            InsAssigningAuthority::nir(),
        );

        $identity = PatientIdentity::qualified($traits, $ins);
        $patient = new PatientReference('patient-2', $identity);

        self::assertNotNull($patient->identity);
        self::assertNotNull($patient->identity->insIdentifier);
        self::assertSame('qualified', $patient->identity->status->value);
        self::assertSame('281102A12500964', (string) $patient->identity->insIdentifier->matricule);
        self::assertSame('1.2.250.1.213.1.4.8', (string) $patient->identity->insIdentifier->authority->oid);
        self::assertSame(['Alan', 'Mathison'], $patient->identity->traits->birthGivenNames);
    }
}
