# healthcare-domain

Shared, framework-agnostic **healthcare domain kernel** for French healthcare applications: patients, practitioners, organizations, medications, and generic clinical concepts — with normalized, checksum-validated value objects for French healthcare reference identifiers.

[![CI](https://github.com/lsoulier42/healthcare-domain/actions/workflows/ci.yml/badge.svg)](https://github.com/lsoulier42/healthcare-domain/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/php-%3E%3D8.2-777BB4?logo=php&logoColor=white)](https://www.php.net/supported-versions.php)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%208-brightgreen)](phpstan.neon.dist)
[![Code Style](https://img.shields.io/badge/style-PSR--12-blue)](phpcs.xml.dist)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue)](LICENSE)

## About

`healthcare-domain` is a pure PHP **domain library**. It models concepts whose semantics remain stable across multiple healthcare applications — identity primitives, identifiers, coded concepts, and generic clinical objects — without imposing any persistence model, workflow, or reference-data catalogue.

- **Framework-free** — no Symfony, no Doctrine, no HTTP. Usable in any PHP 8.2+ project.
- **No persistence contracts** — repository interfaces are the consuming application's concern.
- **No reference-data catalogue** — externally governed terminologies (TRE_G15, TRE_R38, LOINC, SNOMED CT, EDQM, UCUM) are represented as *coded concepts*, not frozen enums or bundled code lists.
- **FHIR-inspired, not a FHIR SDK** — clinical concepts follow FHIR-compatible semantics but there is no FHIR serialization/parsing.
- **Application entities may map** to package objects rather than persist them directly.

> **Status:** pre-1.0. The API is evolving by design; breaking changes are expected until `pres` and a second bounded context have validated it.

## Modules

| Module | Contents |
| --- | --- |
| `Healthcare\Kernel` | CodeSystem / Coding / CodeableConcept, Period, Quantity, Unit (UCUM), Ratio, generic Identifier, Oid, Date, validated identifiers, exceptions |
| `Healthcare\Geographic` | Address, CountryCode, CogCode |
| `Healthcare\Identity` | PatientIdentity, StrictIdentityTraits, InsMatricule, InsAssigningAuthority, InsIdentifier, HumanName, AdministrativeGender, IdentityStatus (RNIV) |
| `Healthcare\Care` | Patient, Practitioner, PractitionerRole, Organization, ContactPoint, coded profession/specialty/category |
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

### 1. Constructing a patient identity

The `Healthcare\Identity` module separates five semantic concepts:

- **`StrictIdentityTraits`** — the strict RNIV/INS identity traits: birth family name, first birth given name, birth date, administrative gender, COG birthplace code. The full birth given-name list (`birthGivenNames`) is optional: the RNIV requires only the first given name for identity creation, the complete list is a trait to complete later.
- **`InsMatricule`** — the 15-character INS matricule component (NIR or NIA with its mod-97 control key). An `InsMatricule` is not a complete INS identifier.
- **`InsAssigningAuthority`** — the authority assigning/interpreting the matricule, identified by its OID.
- **`InsIdentifier`** — the complete INS identifier: matricule + assigning authority.
- **`PatientIdentity`** — strict traits + optional INS identifier + RNIV status.
- **`Nir`** — the generic French social-security number. It is not stored as a parallel identity property on `PatientIdentity`; a consuming application that needs a NIR for billing or administrative purposes stores it separately.

#### Local/provisional identity

```php
use Healthcare\Geographic\ValueObject\CogCode;
use Healthcare\Identity\ValueObject\AdministrativeGender;
use Healthcare\Identity\ValueObject\PatientIdentity;
use Healthcare\Identity\ValueObject\StrictIdentityTraits;
use Healthcare\Kernel\ValueObject\Date;

$traits = new StrictIdentityTraits(
    birthFamilyName: 'LOVELACE',
    firstBirthGivenName: 'Ada',
    birthGivenNames: ['Ada'],
    birthDate: new Date('1815-12-10'),
    gender: AdministrativeGender::FEMALE,
    birthPlace: new CogCode('99100'), // COG birthplace code (here: United Kingdom)
);

// The full given-name list may be unknown while the first name is known:
$minimalTraits = new StrictIdentityTraits(
    birthFamilyName: 'DUPONT',
    firstBirthGivenName: 'Jean',
    birthGivenNames: null,
    birthDate: new Date('1970-01-01'),
    gender: AdministrativeGender::MALE,
    birthPlace: new CogCode('75056'),
);

$identity = PatientIdentity::provisional($traits);

echo $identity->traits->birthFamilyName;       // "LOVELACE"
echo $identity->traits->firstBirthGivenName;   // "Ada"
echo $identity->traits->gender->value;         // "F"
echo $identity->status->value;                 // "provisional"
```

#### Qualified INS identity

```php
use Healthcare\Identity\ValueObject\InsAssigningAuthority;
use Healthcare\Identity\ValueObject\InsIdentifier;
use Healthcare\Identity\ValueObject\InsMatricule;
use Healthcare\Identity\ValueObject\PatientIdentity;

$ins = new InsIdentifier(
    new InsMatricule('185057512345673'), // fake matricule only
    InsAssigningAuthority::nir(),
);

$identity = PatientIdentity::qualified($traits, $ins);

echo (string) $identity->insIdentifier->matricule;       // "185057512345673"
echo (string) $identity->insIdentifier->authority->oid;  // "1.2.250.1.213.1.4.8"
```

The four RNIV statuses map to factories:

```php
PatientIdentity::provisional($traits);              // no INS
PatientIdentity::validated($traits);                // no INS
PatientIdentity::recovered($traits, $ins);          // INS required
PatientIdentity::qualified($traits, $ins);          // INS required
```

Incoherent states (e.g. `QUALIFIED` without an INS) cannot be built: `PatientIdentity` has no public constructor, only the four named factories.

### 2. Representing a practitioner role with coded profession/specialty

```php
use Healthcare\Care\Entity\Organization;
use Healthcare\Care\Entity\Practitioner;
use Healthcare\Care\Entity\PractitionerRole;
use Healthcare\Care\ValueObject\ProfessionCode;
use Healthcare\Care\ValueObject\SpecialtyCode;
use Healthcare\Identity\ValueObject\HumanName;

$practitioner = new Practitioner('p-1', new HumanName('Lovelace', ['Ada']));
$organization = new Organization('o-1', 'Example Clinic');

$role = new PractitionerRole(
    'role-1',
    $practitioner,
    $organization,
    profession: ProfessionCode::fromTreG15('10', 'Médecin'),
    specialty: SpecialtyCode::fromTreR38('SM41', 'Pneumologie'),
);

echo $role->profession()->concept->code; // "10" — TRE_G15
```

### 3. Creating a medication with CIS and multiple CIP presentations

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

### 4. Representing a lab observation with quantity/reference range

```php
use Healthcare\Clinical\Entity\Observation;
use Healthcare\Clinical\ValueObject\ReferenceRange;
use Healthcare\Clinical\ValueObject\ObservationCode;
use Healthcare\Clinical\ValueObject\ObservationStatus;
use Healthcare\Clinical\ValueObject\QuantityValue;
use Healthcare\Kernel\ValueObject\Quantity;
use Healthcare\Kernel\ValueObject\Unit;

$observation = new Observation(
    'obs-1',
    $patient,
    ObservationCode::fromLoinc('718-7', 'Hemoglobin'),
    ObservationStatus::FINAL,
    new QuantityValue(new Quantity('140', Unit::fromUcum('g/L'))),
);
$observation->addReferenceRange(new ReferenceRange(
    low: new Quantity('120', Unit::fromUcum('g/L')),
    high: new Quantity('160', Unit::fromUcum('g/L')),
));

echo $observation->value()->quantity; // "140 g/L"
```

### 5. Representing an imaging request/report with a DICOM Study Instance UID

```php
use Healthcare\Imaging\Entity\ImagingStudy;
use Healthcare\Imaging\ValueObject\StudyInstanceUid;
use Healthcare\Clinical\Entity\ServiceRequest;

$study = new ImagingStudy(
    'study-1',
    $patient,
    StudyInstanceUid::tryFrom('2.25.12345678901234567890123456789012345678901'),
    request: $request,
);

echo (string) $study->studyInstanceUid(); // the validated DICOM UID
```

## Design principles

- **Typed identifiers** — never pass raw strings around; every identifier is a dedicated immutable value object.
- **Fail fast or fail soft** — constructors throw (`InvalidValueObject`, `InvalidIdentifier`, `InvalidPeriod`, `InvalidDomainState`); `isValidValue()` and `tryFrom()` validate external data without exceptions.
- **Value semantics** — value objects are immutable, normalized on construction, and comparable with `equals()`.
- **Coded concepts over enums** — externally governed terminologies are `Coding` instances (FHIR Coding semantics), and `CodeableConcept` supports several codings plus a text, so historical and future codes stay representable.
- **Explicit collections** — entity collections reject semantically duplicate values.

## Project layout

```
src/
  Kernel/       # identifiers, coded concepts, primitives, errors
  Geographic/   # Address, CountryCode, CogCode
  Identity/     # PatientIdentity, StrictIdentityTraits, InsMatricule, InsAssigningAuthority, InsIdentifier, HumanName, AdministrativeGender, IdentityStatus
  Care/         # Patient, Practitioner, PractitionerRole, Organization
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
