<?php

declare(strict_types=1);

namespace Healthcare\Tests\Clinical;

use Closure;
use DateTimeImmutable;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\Entity\ClinicalDocument;
use Healthcare\Clinical\Entity\DiagnosticReport;
use Healthcare\Clinical\Entity\Encounter;
use Healthcare\Clinical\Entity\Observation;
use Healthcare\Clinical\Entity\ServiceRequest;
use Healthcare\Clinical\Entity\Specimen;
use Healthcare\Clinical\ValueObject\DiagnosticReportStatus;
use Healthcare\Clinical\ValueObject\ObservationCode;
use Healthcare\Clinical\ValueObject\ObservationStatus;
use Healthcare\Clinical\ValueObject\ServiceRequestStatus;
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Imaging\Entity\ImagingStudy;
use Healthcare\Imaging\ValueObject\DicomUid;
use Healthcare\Imaging\ValueObject\StudyInstanceUid;
use Healthcare\Kernel\Exception\InvalidDomainState;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Date;
use Healthcare\Kernel\ValueObject\Period;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class PatientConsistencyTest extends TestCase
{
    /** @param Closure(PatientReference, ?Encounter): (ServiceRequest|ClinicalDocument|DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('encounterResources')]
    public function testConstructorRejectsAnotherPatientsEncounter(Closure $create): void
    {
        $encounter = new Encounter('encounter', new PatientReference('patient-b'), new Period());

        $this->expectException(InvalidDomainState::class);
        $create(new PatientReference('patient-a'), $encounter);
    }

    /** @param Closure(PatientReference, ?Encounter): (ServiceRequest|ClinicalDocument|DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('encounterResources')]
    public function testRejectedEncounterChangePreservesPreviousAssociation(Closure $create): void
    {
        $patient = new PatientReference('patient-a');
        $original = new Encounter('original', $patient, new Period());
        $resource = $create($patient, $original);
        $other = new Encounter('other', new PatientReference('patient-b'), new Period());

        $this->expectException(InvalidDomainState::class);
        try {
            $resource->changeEncounter($other);
        } finally {
            self::assertSame($original, $resource->encounter());
        }
    }

    /** @param Closure(PatientReference, ?Encounter): (ServiceRequest|ClinicalDocument|DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('encounterResources')]
    public function testEncounterMatchingUsesRecordIdAndAllowsUnlinking(Closure $create): void
    {
        $patient = new PatientReference('patient-a');
        $encounter = new Encounter('encounter', $this->patientWithSnapshot(), new Period());
        $resource = $create($patient, $encounter);
        self::assertSame($encounter, $resource->encounter());

        $resource->changeEncounter(null);
        self::assertNull($resource->encounter());
        $resource->changeEncounter($encounter);
        self::assertSame($encounter, $resource->encounter());
        self::assertNull($create($patient, null)->encounter());
    }

    /** @return iterable<string, array{Closure(PatientReference, ?Encounter): (ServiceRequest|ClinicalDocument|DiagnosticReport|ImagingStudy)}> */
    public static function encounterResources(): iterable
    {
        yield 'service request' => [static fn (PatientReference $patient, ?Encounter $encounter): ServiceRequest =>
            new ServiceRequest(
                'request',
                $patient,
                new CodeableConcept([], 'Test request'),
                ServiceRequestStatus::ACTIVE,
                encounter: $encounter,
            )];

        yield 'clinical document' => [static fn (PatientReference $patient, ?Encounter $encounter): ClinicalDocument =>
            new ClinicalDocument(
                'document',
                $patient,
                new CodeableConcept([], 'Test document'),
                new PractitionerReference('author'),
                new DateTimeImmutable('2025-01-01'),
                encounter: $encounter,
            )];
        yield 'diagnostic report' => [static fn (PatientReference $patient, ?Encounter $encounter): DiagnosticReport =>
            new DiagnosticReport(
                'report',
                $patient,
                new CodeableConcept([], 'Test report'),
                DiagnosticReportStatus::FINAL,
                encounter: $encounter,
            )];
        yield 'imaging study' => [static fn (PatientReference $patient, ?Encounter $encounter): ImagingStudy =>
            new ImagingStudy('study', $patient, new StudyInstanceUid(new DicomUid('2.25.123')), encounter: $encounter)];
    }

    /** @param Closure(PatientReference, ?ServiceRequest): (DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('requestResources')]
    public function testConstructorRejectsAnotherPatientsRequest(Closure $create): void
    {
        $request = $this->request(new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        $create(new PatientReference('patient-a'), $request);
    }

    /** @param Closure(PatientReference, ?ServiceRequest): (DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('requestResources')]
    public function testRejectedRequestChangePreservesPreviousAssociation(Closure $create): void
    {
        $patient = new PatientReference('patient-a');
        $original = $this->request($patient);
        $resource = $create($patient, $original);
        $other = $this->request(new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        try {
            $resource->changeRequest($other);
        } finally {
            self::assertSame($original, $resource->request());
        }
    }

    /** @param Closure(PatientReference, ?ServiceRequest): (DiagnosticReport|ImagingStudy) $create */
    #[DataProvider('requestResources')]
    public function testRequestMatchingUsesRecordIdAndAllowsUnlinking(Closure $create): void
    {
        $patient = new PatientReference('patient-a');
        $request = $this->request($this->patientWithSnapshot());
        $resource = $create($patient, $request);
        self::assertSame($request, $resource->request());

        $resource->changeRequest(null);
        self::assertNull($resource->request());
        $resource->changeRequest($request);
        self::assertSame($request, $resource->request());
        self::assertNull($create($patient, null)->request());
    }

    /** @return iterable<string, array{Closure(PatientReference, ?ServiceRequest): (DiagnosticReport|ImagingStudy)}> */
    public static function requestResources(): iterable
    {
        yield 'diagnostic report' => [static fn (
            PatientReference $patient,
            ?ServiceRequest $request,
        ): DiagnosticReport => new DiagnosticReport(
            'report',
            $patient,
            new CodeableConcept([], 'Test report'),
            DiagnosticReportStatus::FINAL,
            request: $request,
        )];
        yield 'imaging study' => [static fn (PatientReference $patient, ?ServiceRequest $request): ImagingStudy =>
            new ImagingStudy('study', $patient, new StudyInstanceUid(new DicomUid('2.25.123')), request: $request)];
    }

    public function testReportConstructorRejectsAnotherPatientsDocument(): void
    {
        $document = $this->document(new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        new DiagnosticReport(
            'report',
            new PatientReference('patient-a'),
            new CodeableConcept([], 'Test report'),
            DiagnosticReportStatus::FINAL,
            document: $document,
        );
    }

    public function testRejectedDocumentChangePreservesPreviousAssociation(): void
    {
        $report = $this->report();
        $original = $this->document($this->patientWithSnapshot());
        $report->changeDocument($original);
        $other = $this->document(new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        try {
            $report->changeDocument($other);
        } finally {
            self::assertSame($original, $report->document());
        }
    }

    public function testDocumentMatchingUsesRecordIdAndAllowsUnlinking(): void
    {
        $document = $this->document($this->patientWithSnapshot());
        $report = new DiagnosticReport(
            'report',
            new PatientReference('patient-a'),
            new CodeableConcept([], 'Test report'),
            DiagnosticReportStatus::FINAL,
            document: $document,
        );
        self::assertSame($document, $report->document());
        $report->changeDocument(null);
        self::assertNull($report->document());
        $report->changeDocument($document);
        self::assertSame($document, $report->document());
    }

    public function testObservationConstructorRejectsAnotherPatientsSpecimen(): void
    {
        $specimen = new Specimen('specimen', new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        $this->observation(new PatientReference('patient-a'), $specimen);
    }

    public function testRejectedSpecimenChangePreservesPreviousAssociation(): void
    {
        $patient = new PatientReference('patient-a');
        $original = new Specimen('original', $patient);
        $observation = $this->observation($patient, $original);
        $other = new Specimen('other', new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        try {
            $observation->changeSpecimen($other);
        } finally {
            self::assertSame($original, $observation->specimen());
        }
    }

    public function testObservationAcceptsMatchingAndUnknownSpecimenPatients(): void
    {
        $patient = new PatientReference('patient-a');
        foreach ([new Specimen('known', $this->patientWithSnapshot()), new Specimen('unknown')] as $specimen) {
            $observation = $this->observation($patient, $specimen);
            self::assertSame($specimen, $observation->specimen());
            $observation->changeSpecimen(null);
            self::assertNull($observation->specimen());
            $observation->changeSpecimen($specimen);
            self::assertSame($specimen, $observation->specimen());
        }
    }

    #[DataProvider('identifierConflicts')]
    public function testReportRejectsAnotherPatientsResultWithoutChangingItsCollection(bool $sameId): void
    {
        $report = $this->report();
        $original = $this->observation($this->patientWithSnapshot());
        $report->addResult($original);
        $other = $this->observation(new PatientReference('patient-b'), id: $sameId ? 'observation' : 'other');

        $this->expectException(InvalidDomainState::class);
        try {
            $report->addResult($other);
        } finally {
            self::assertSame([$original], $report->results());
        }
    }

    #[DataProvider('identifierConflicts')]
    public function testReportRejectsAnotherPatientsSpecimenWithoutChangingItsCollection(bool $sameId): void
    {
        $report = $this->report();
        $original = new Specimen('specimen', $this->patientWithSnapshot());
        $report->addSpecimen($original);
        $other = new Specimen($sameId ? 'specimen' : 'other', new PatientReference('patient-b'));

        $this->expectException(InvalidDomainState::class);
        try {
            $report->addSpecimen($other);
        } finally {
            self::assertSame([$original], $report->specimens());
        }
    }

    /** @return iterable<string, array{bool}> */
    public static function identifierConflicts(): iterable
    {
        yield 'distinct resource ids' => [false];
        yield 'same resource id must not bypass patient validation' => [true];
    }

    public function testReportAcceptsMatchingResultsAndMatchingOrUnknownSpecimens(): void
    {
        $report = $this->report();
        $observation = $this->observation($this->patientWithSnapshot());
        $report->addResult($observation);
        $report->addResult($observation);
        self::assertSame([$observation], $report->results());

        $known = new Specimen('known', $this->patientWithSnapshot());
        $unknown = new Specimen('unknown');
        $report->addSpecimen($known);
        $report->addSpecimen($unknown);
        $report->addSpecimen($unknown);
        self::assertSame([$known, $unknown], $report->specimens());
    }

    public function testSharedSpecimenCannotBeReassignedInPlace(): void
    {
        $patient = new PatientReference('patient-a');
        $specimen = new Specimen('specimen', $patient);
        $observation = $this->observation($patient, $specimen);
        $report = $this->report();
        $report->addSpecimen($specimen);
        $report->addResult($observation);

        // Reassignment is deliberately absent from the public API.
        self::assertNotContains('changePatient', get_class_methods($specimen));
        $specimen->changeReceivedAt(new DateTimeImmutable('2025-01-02'));
        self::assertSame($patient, $report->specimens()[0]->patient());
        self::assertSame($patient, $observation->specimen()?->patient());
    }

    public function testAttributingAnUnknownSpecimenRequiresExplicitReplacement(): void
    {
        $unknown = new Specimen('specimen');
        $patient = new PatientReference('patient-a');
        $observation = $this->observation($patient, $unknown);
        $report = $this->report();
        $report->addSpecimen($unknown);
        $assigned = new Specimen($unknown->id(), $patient);

        self::assertNull($observation->specimen()?->patient());
        self::assertNull($report->specimens()[0]->patient());
        $observation->changeSpecimen($assigned);
        $report->removeSpecimen($unknown);
        $report->addSpecimen($assigned);
        self::assertSame($assigned, $observation->specimen());
        self::assertSame([$assigned], $report->specimens());
        self::assertNull($unknown->patient());
    }

    private function observation(
        PatientReference $patient,
        ?Specimen $specimen = null,
        string $id = 'observation',
    ): Observation {
        return new Observation(
            $id,
            $patient,
            new ObservationCode(new CodeableConcept([], 'Test observation')),
            ObservationStatus::FINAL,
            specimen: $specimen,
        );
    }

    private function report(): DiagnosticReport
    {
        return new DiagnosticReport(
            'report',
            new PatientReference('patient-a'),
            new CodeableConcept([], 'Test report'),
            DiagnosticReportStatus::FINAL,
        );
    }

    private function document(PatientReference $patient): ClinicalDocument
    {
        return new ClinicalDocument(
            'document',
            $patient,
            new CodeableConcept([], 'Test document'),
            new PractitionerReference('author'),
            new DateTimeImmutable('2025-01-01'),
        );
    }

    private function request(PatientReference $patient): ServiceRequest
    {
        return new ServiceRequest('request', $patient, new CodeableConcept([], 'Test'), ServiceRequestStatus::ACTIVE);
    }

    private function patientWithSnapshot(): PatientReference
    {
        return new PatientReference('patient-a', PatientIdentity::provisional(new StrictIdentityTraits(
            birthFamilyName: 'EXAMPLE',
            firstBirthGivenName: 'Alice',
            birthGivenNames: ['Alice'],
            birthDate: new Date('2000-01-01'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99999'),
        )));
    }
}
