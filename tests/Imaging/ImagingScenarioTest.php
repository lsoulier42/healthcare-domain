<?php

declare(strict_types=1);

namespace Healthcare\Tests\Imaging;

use DateTimeImmutable;
use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Patient;
use Healthcare\Clinical\Entity\ServiceRequest;
use Healthcare\Clinical\ValueObject\ServiceRequestStatus;
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Imaging\Entity\ImagingStudy;
use Healthcare\Imaging\ValueObject\AccessionNumber;
use Healthcare\Imaging\ValueObject\DicomUid;
use Healthcare\Imaging\ValueObject\ModalityCode;
use Healthcare\Imaging\ValueObject\SeriesInstanceUid;
use Healthcare\Imaging\ValueObject\SopInstanceUid;
use Healthcare\Imaging\ValueObject\StudyInstanceUid;
use Healthcare\Kernel\Exception\InvalidIdentifier;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Coding;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Date;
use PHPUnit\Framework\TestCase;

final class ImagingScenarioTest extends TestCase
{
    /**
     * Source: NEMA PS3.5 §9 (VR UI) — ≤ 64 chars, dotted integer
     * components, no leading zeros except "0", ≥ 2 components.
     */
    public function testDicomUidValidation(): void
    {
        $uid = new DicomUid('2.25.12345678901234567890123456789012345678901');

        self::assertSame('2.25.12345678901234567890123456789012345678901', (string) $uid);
        self::assertTrue(DicomUid::isValidValue('2.25.123456789012345678901234567890'));
        self::assertTrue(DicomUid::isValidValue('1.2.3.0.4'));
        self::assertFalse(DicomUid::isValidValue('1.2.03.4')); // leading zero
        self::assertFalse(DicomUid::isValidValue('1..2')); // empty component
        self::assertFalse(DicomUid::isValidValue('1.2' . str_repeat('.3', 40))); // > 64 chars
        self::assertFalse(DicomUid::isValidValue('12345')); // single component
        self::assertNull(DicomUid::tryFrom('1.2.03'));
    }

    public function testInvalidDicomUidIsRejected(): void
    {
        $this->expectException(InvalidIdentifier::class);
        new DicomUid('1.2.03');
    }

    public function testSemanticWrappersPreventInterchange(): void
    {
        $study = new StudyInstanceUid(new DicomUid('2.25.12345678901234567890123456789012345678901'));
        $series = new SeriesInstanceUid(new DicomUid('2.25.12345678901234567890123456789012345678902'));
        $sop = new SopInstanceUid(new DicomUid('2.25.12345678901234567890123456789012345678903'));

        self::assertSame('2.25.12345678901234567890123456789012345678901', (string) $study);
        self::assertSame('2.25.12345678901234567890123456789012345678902', (string) $series);
        self::assertSame('2.25.12345678901234567890123456789012345678903', (string) $sop);
        self::assertNull(StudyInstanceUid::tryFrom('bad uid'));
    }

    public function testImagingRequestStudyReportScenarioComposes(): void
    {
        $traits = new StrictIdentityTraits(
            birthFamilyName: 'LOVELACE',
            firstBirthGivenName: 'Ada',
            birthGivenNames: ['Ada'],
            birthDate: new Date('1815-12-10'),
            gender: AdministrativeGender::FEMALE,
            birthPlace: new CogCode('99100'),
        );
        $patient = new Patient('patient-1', PatientIdentity::provisional($traits));
        $organization = new Organization('o-1', 'Centre Imagerie');

        $request = new ServiceRequest(
            'sr-1',
            $patient,
            new CodeableConcept([new Coding(CodeSystem::loinc(), '36643-5', 'CT Chest')]),
            ServiceRequestStatus::ACTIVE,
            performerOrganization: $organization,
        );

        $studyUid = StudyInstanceUid::tryFrom('2.25.12345678901234567890123456789012345678901');
        self::assertNotNull($studyUid);

        $study = new ImagingStudy(
            'study-1',
            $patient,
            $studyUid,
            request: $request,
            accessionNumber: new AccessionNumber('ACC-2025-0001'),
            startedAt: new DateTimeImmutable('2025-03-01 14:00'),
            organization: $organization,
        );
        $study->addModality(ModalityCode::fromDicom('CT', 'Computed Tomography'));
        $study->addModality(ModalityCode::fromDicom('CT'));

        self::assertCount(1, $study->modalities());
        self::assertSame('CT', $study->modalities()[0]->coding->code);
        self::assertSame('ACC-2025-0001', (string) $study->accessionNumber());
        self::assertSame($request, $study->request());
        self::assertSame($patient, $study->patient());
    }
}
