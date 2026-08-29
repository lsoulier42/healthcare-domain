# healthcare-domain

Shared, framework-agnostic **healthcare domain kernel** for French healthcare applications: identity value objects, validated identifiers, profession/savoir-faire codes, and generic clinical resources — with normalized, checksum-validated value objects for French healthcare reference identifiers.

[![CI](https://github.com/lsoulier42/healthcare-domain/actions/workflows/ci.yml/badge.svg)](https://github.com/lsoulier42/healthcare-domain/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon.dist)
[![Code Style](https://img.shields.io/badge/style-PSR--12-blue)](phpcs.xml.dist)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## About

`healthcare-domain` is a pure PHP **domain library**. It owns reusable healthcare semantics and typed clinical resources. Consuming applications own persistence and application aggregates, and compose package identity/value objects instead of inheriting package entities.

A consumer should **never have to redefine** what an INS patient identity, a practitioner professional identity, a healthcare organization identity, a profession or a professional savoir-faire means. A consumer **does** own its persistence models, application aggregates and workflows.

- **Framework-free** — no Symfony, no Doctrine, no HTTP. Usable in any PHP 8.2+ project.
- **Composition over inheritance** — the package defines semantic building blocks; consumers define their aggregates and persistence; clinical package resources refer to consumer records through typed references.
- **No persistence contracts** — repository interfaces are the consuming application's concern.
- **No reference-data catalogue** — externally governed terminologies (TRE_G15, TRE_R38, TRE_R40, LOINC, SNOMED CT, EDQM, UCUM) are represented as *coded concepts*, not frozen enums or bundled code lists.
- **FHIR-inspired, not a FHIR SDK** — clinical concepts follow FHIR-compatible semantics but there is no FHIR serialization/parsing.

> **Status:** pre-1.0. The API is evolving by design; breaking changes are expected until `pres` and a second bounded context have validated it.

## Modules

| Module | Contents |
| --- | --- |
| `Healthcare\Kernel` | CodeSystem / Coding / CodeableConcept, Period, Quantity, Unit (UCUM), Ratio, generic Identifier, Oid, Date, validated identifiers, exceptions |
| `Healthcare\Geographic` | Address, CountryCode, CogCode |
| `Healthcare\Identity` | PatientIdentity, StrictIdentityTraits, InsMatricule, InsAssigningAuthority, InsIdentifier, HumanName, AdministrativeGender, IdentityStatus (RNIV) |
| `Healthcare\Care` | PractitionerIdentity, OrganizationIdentity, PractitionerRole, PatientReference, PractitionerReference, OrganizationReference, ContactPoint, profession/savoir-faire/category codes |
| `Healthcare\Clinical` | Encounter, ServiceRequest, Observation, ReferenceRange, DiagnosticReport, Specimen, ClinicalDocument |
| `Healthcare\Medication` | Medication (CIS), MedicationPresentation (CIP/UCD), MedicationComponent, ActiveSubstance |
| `Healthcare\Laboratory` | AccessionNumber |
| `Healthcare\Imaging` | DicomUid + Study/Series/SOP instance UIDs, imaging AccessionNumber, ModalityCode, ImagingStudy |

### Validated French identifiers

| Value object | Identifier | Validation |
| --- | --- | --- |
| `Rpps` | Practitioner ID (11 digits) | Format |
| `Adeli` | Legacy practitioner ID (9 characters) | Format, separators stripped |
| `Finess` | Facility ID (9 digits) | Weighted checksum |
| `Siren` | Organization ID (9 digits) | Luhn checksum |
| `Siret` | Establishment ID (14 digits) | Luhn + embedded Siren checksum |
| `InsMatricule` | INS matricule (15 characters) | NIR or NIA with mod-97 control key |
| `Nir` | Social security number (13 or 15 digits) | Format; mod-97 control key on 15 digits |
| `Oid` | ISO object identifier (dotted-decimal arcs) | Syntax + X.660 root arcs |
| `Date` | Calendar date (YYYY-MM-DD, no time/zone) | Real calendar date |
| `Cis` | Medicinal product ID (8 digits) | Format |
| `Cip7` / `Cip13` | Drug presentation codes | Luhn checksum |
| `Ucd` | Dispensing-unit code (7 digits) | Format |
| `Atc` | ATC classification code | Format |
| `CountryCode` | Country (ISO 3166-1 alpha-2) | Format |
| `CogCode` | Birthplace COG code (5 chars: commune, 99+country, 99999) | Format |

All identifiers normalize their input (trimming, casing, separator stripping) and expose `isValidValue()`, `tryFrom()`, `equals()` and `__toString()`.

## Installation

```bash
composer require lsoulier42/healthcare-domain
```

**Requirements:** PHP >= 8.2. No runtime dependencies.

## Usage

### 1. Composing a patient identity in an application patient record

The `Healthcare\Identity` module separates five semantic concepts:

- **`StrictIdentityTraits`** — the strict RNIV/INS identity traits: birth family name, first birth given name, birth date, administrative gender, COG birthplace code. The full birth given-name list (`birthGivenNames`) is optional: the RNIV requires only the first given name for identity creation, the complete list is a trait to complete later.
- **`InsMatricule`** — the 15-character INS matricule component (NIR or NIA with its mod-97 control key). An `InsMatricule` is not a complete INS identifier.
- **`InsAssigningAuthority`** — the authority assigning/interpreting the matricule, identified by its OID.
- **`InsIdentifier`** — the complete INS identifier: matricule + assigning authority.
- **`PatientIdentity`** — strict traits + optional INS identifier + RNIV status.

The application owns its patient record; it composes a `PatientIdentity` when the strict shared identity can truthfully be represented:

```php
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\ValueObject\Date;

final class Patient
{
    public function __construct(
        private ?PatientIdentity $identity,
    ) {
    }

    public function healthcareIdentity(): ?PatientIdentity
    {
        return $this->identity;
    }

    public function replaceHealthcareIdentity(PatientIdentity $identity): void
    {
        $this->identity = $identity;
    }
}

$traits = new StrictIdentityTraits(
    birthFamilyName: 'LOVELACE',
    firstBirthGivenName: 'Ada',
    birthGivenNames: ['Ada'],
    birthDate: new Date('1815-12-10'),
    gender: AdministrativeGender::FEMALE,
    birthPlace: new CogCode('99100'), // COG birthplace code (here: United Kingdom)
);

$patient = new Patient(PatientIdentity::provisional($traits));

echo $patient->healthcareIdentity()->traits->birthFamilyName; // "LOVELACE"
echo $patient->healthcareIdentity()->status->value;           // "provisional"
```

The four RNIV statuses map to factories:

```php
PatientIdentity::provisional($traits);              // no INS
PatientIdentity::validated($traits);                // no INS
PatientIdentity::recovered($traits, $ins);          // INS required
PatientIdentity::qualified($traits, $ins);          // INS required
```

Incoherent states (e.g. `QUALIFIED` without an INS) cannot be built: `PatientIdentity` has no public constructor, only the four named factories.

### 2. Representing a practitioner identity

`PractitionerIdentity` carries the stable professional identity (name, optional RPPS, optional ADELI). Organization membership, roles and savoir-faire live in `PractitionerRole`:

```php
use Healthcare\Care\ValueObject\PractitionerIdentity;
use Healthcare\Care\ValueObject\PractitionerRole;
use Healthcare\Care\ValueObject\ProfessionCode;
use Healthcare\Care\ValueObject\SavoirFaireCode;
use Healthcare\Identity\ValueObject\HumanName;
use Healthcare\Kernel\ValueObject\Rpps;

$identity = new PractitionerIdentity(
    new HumanName('Curie', ['Marie']),
    rpps: new Rpps('12345678901'),
);

$role = new PractitionerRole(
    profession: ProfessionCode::fromTreG15('10', 'Médecin'),
    savoirFaire: [
        SavoirFaireCode::fromTreR38('SM41', 'Pneumologie'),  // ordinal specialty
        SavoirFaireCode::fromTreR40('CEX01'),                // exclusive competence
    ],
);

echo $role->profession->coding->code; // "10" — TRE_G15
echo $role->savoirFaire[0]->coding->code; // "SM41" — TRE_R38
```

### 3. Representing an organization identity

```php
use Healthcare\Care\ValueObject\OrganizationIdentity;
use Healthcare\Kernel\ValueObject\Siren;
use Healthcare\Kernel\ValueObject\Siret;

$identity = new OrganizationIdentity(
    name: 'Example Hospital',
    siren: new Siren('732829320'),
    siret: new Siret('73282932000074'),
);

echo $identity->name; // "Example Hospital"
```

When both SIREN and SIRET are supplied, they must refer to the same legal entity (the SIREN is derived from the SIRET and compared).

### 4. Creating a medication with CIS and multiple CIP presentations

```php
use Healthcare\Kernel\ValueObject\Cip13;
use Healthcare\Kernel\ValueObject\Cis;
use Healthcare\Medication\Entity\Medication;
use Healthcare\Medication\Entity\MedicationPresentation;

$medication = new Medication('med-1', 'Example product', cis: new Cis('60000000'));

$presentation = new MedicationPresentation(
    'pres-1',
    $medication,
    cip13: new Cip13('3400931234560'), // fake CIP
    packagingDescription: 'Box of 16 tablets',
);

echo (string) $medication->cis();      // "60000000"
echo count($medication->presentations()); // 1
```

### 5. Referring to patient and practitioner records from a clinical resource

Generic clinical resources refer to consumer records through **typed references**, not through package entities:

```php
use Healthcare\Care\ValueObject\OrganizationReference;
use Healthcare\Care\ValueObject\PatientReference;
use Healthcare\Care\ValueObject\PractitionerReference;
use Healthcare\Clinical\Entity\Observation;
use Healthcare\Clinical\Entity\ServiceRequest;
use Healthcare\Clinical\ValueObject\ObservationCode;
use Healthcare\Clinical\ValueObject\ObservationStatus;
use Healthcare\Clinical\ValueObject\QuantityValue;
use Healthcare\Clinical\ValueObject\ServiceRequestStatus;
use Healthcare\Kernel\ValueObject\CodeableConcept;
use Healthcare\Kernel\ValueObject\Coding;
use Healthcare\Kernel\ValueObject\CodeSystem;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\Unit;

$patientRef = new PatientReference(
    id: 'patient-1',
    identity: $patient->healthcareIdentity(), // optional snapshot
);

$practitionerRef = new PractitionerReference(id: 'practitioner-1');

$request = new ServiceRequest(
    'sr-1',
    $patientRef,
    new CodeableConcept([new Coding(CodeSystem::loinc(), '58410-2', 'CBC panel')]),
    ServiceRequestStatus::ACTIVE,
    requester: $practitionerRef,
);

$observation = new Observation(
    'obs-1',
    $patientRef,
    ObservationCode::fromLoinc('718-7', 'Hemoglobin [Mass/volume] in Blood'),
    ObservationStatus::FINAL,
    new QuantityValue(new Quantity('140', Unit::fromUcum('g/L'))),
);

echo $observation->value()->quantity; // "140 g/L"
```

Reference equality is based on the record `id` only: the same referenced record remains the same reference even if its name or identity snapshot is updated later.

### 6. Representing an imaging request/report with a DICOM Study Instance UID

```php
use Healthcare\Imaging\Entity\ImagingStudy;
use Healthcare\Imaging\ValueObject\StudyInstanceUid;

$study = new ImagingStudy(
    'study-1',
    $patientRef,
    StudyInstanceUid::tryFrom('2.25.12345678901234567890123456789012345678901'),
    request: $request,
);

echo (string) $study->studyInstanceUid(); // the validated DICOM UID
```

## Design principles

- **Composition over application-aggregate inheritance** — `healthcare-domain` defines semantic building blocks; consumers define their aggregates and persistence; clinical package resources refer to consumer records through typed references.
- **Typed identifiers** — never pass raw strings around; every identifier is a dedicated immutable value object.
- **Fail fast or fail soft** — constructors throw (`InvalidValueObject`, `InvalidIdentifier`, `InvalidPeriod`, `InvalidDomainState`); `isValidValue()` and `tryFrom()` validate external data without exceptions.
- **Value semantics** — value objects are immutable, normalized on construction, and comparable with `equals()`.
- **Coded concepts over enums** — externally governed terminologies are `Coding` instances (FHIR Coding semantics), and `CodeableConcept` supports several codings plus a text, so historical and future codes stay representable.
- **Explicit collections** — entity collections reject semantically duplicate values.

## Migrating from 0.1.x to 0.2.0

`0.2.0` is a breaking pre-1.0 evolution. The application-shaped `Care\Entity\*` aggregates have been removed: the package no longer ships `Patient`, `Practitioner`, `Organization` or a mutable `PractitionerRole` entity.

| 0.1.x | 0.2.x |
| --- | --- |
| `Care\Entity\Patient` | consumer `Patient` aggregate + `PatientIdentity`, `PatientReference` when referenced |
| `Care\Entity\Practitioner` | consumer `Practitioner` aggregate + `PractitionerIdentity`, `PractitionerRole[]`, `PractitionerReference` when referenced |
| `Care\Entity\Organization` | consumer `Organization` aggregate + `OrganizationIdentity`, `OrganizationReference` when referenced |
| `Care\Entity\PractitionerRole` | `Care\ValueObject\PractitionerRole` (immutable) |
| `SpecialtyCode` | `SavoirFaireCode` (TRE_R38 / TRE_R40) |

Consumers should not map their whole aggregate to a second package aggregate anymore. Preferred consumer style:

```php
$identity = $patient->healthcareIdentity();
$reference = new PatientReference((string) $patient->getUuid(), $identity);
```

and when mutating shared identity semantics:

```php
$identity = PatientIdentity::qualified($traits, $ins);
$patient->replaceHealthcareIdentity($identity);
```

The package factory/value object is the source of truth for the shared invariant; the consumer only decides how to persist it.

## Project layout

```
src/
  Kernel/       # identifiers, coded concepts, primitives, errors
  Geographic/   # Address, CountryCode, CogCode
  Identity/     # PatientIdentity, StrictIdentityTraits, InsMatricule, InsAssigningAuthority, InsIdentifier, HumanName, AdministrativeGender, IdentityStatus
  Care/         # PractitionerIdentity, OrganizationIdentity, PractitionerRole, Patient/Practitioner/OrganizationReference, ContactPoint, profession/savoir-faire/category codes
  Clinical/     # Encounter, ServiceRequest, Observation, DiagnosticReport, Specimen, ClinicalDocument
  Medication/   # Medication, MedicationPresentation, MedicationComponent, ActiveSubstance
  Laboratory/   # AccessionNumber
  Imaging/      # DicomUid family, AccessionNumber, ModalityCode, ImagingStudy
tests/          # unit + cross-module scenario tests, no database required
```

## Testing

```bash
composer install
composer test       # PHPUnit
composer phpstan    # PHPStan (level 8)
composer cs         # PHPCS (PSR-12)
```

Or with Docker, using the same environment as CI:

```bash
docker compose run --rm php test
docker compose run --rm php phpstan -- --configuration=phpstan.neon.dist
docker compose run --rm php cs
```

## Security

- **No real data, ever.** No real RPPS/FINESS/INS identifiers, no personal data, no secrets. Fixtures and examples use fake data only.
- To report a vulnerability, please contact the maintainer privately (open a draft issue or reach out directly) rather than opening a public issue.

## Contributing

Pull requests are welcome. Keep in mind:

- the core stays framework-free and depends on `php` only;
- all identifiers remain typed — no raw strings crossing boundaries;
- new behavior ships with unit tests, and `composer test && composer phpstan && composer cs` must stay green.

## License

[MIT](LICENSE) © [Louise Soulier](https://github.com/lsoulier42)
