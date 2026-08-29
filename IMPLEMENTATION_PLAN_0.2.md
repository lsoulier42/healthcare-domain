# healthcare-domain 0.2 — Composable identity and reference model

## Objective

Refactor `healthcare-domain` after the first real consumer integration (`pres`) showed that the current `Care\Entity\Patient`, `Practitioner`, `Organization` and `PractitionerRole` sit at the wrong architectural boundary.

The package must define **shared healthcare semantics and invariants**, while consuming applications remain free to define their own aggregates, persistence models and workflows.

The target is **composition, not inheritance and not parallel mapped aggregates**:

```text
healthcare-domain                         consuming application
─────────────────                         ─────────────────────
PatientIdentity       ◄────────────────── App\Entity\Patient
PractitionerIdentity  ◄────────────────── App\Entity\Practitioner
OrganizationIdentity  ◄────────────────── App\Entity\Organization
PractitionerRole      ◄────────────────── application role/profile model

PatientReference      ◄────────────────── stable reference to the app Patient record
PractitionerReference ◄────────────────── stable reference to the app Practitioner record
OrganizationReference ◄────────────────── stable reference to the app Organization record
```

A consumer should **never have to redefine what an INS identity, practitioner professional identity, healthcare organization identity, profession or professional savoir-faire means**.

A consumer **does** own concepts such as:

- Doctrine persistence and database IDs;
- patient ownership / tenancy;
- prescriptions;
- user accounts;
- medical history;
- laboratory workflow;
- imaging workflow;
- organization configuration;
- application lifecycle and audit;
- merge/deduplication workflows.

This is a breaking pre-1.0 evolution intended for release as `0.2.0`.

---

# 1. Architectural decisions

## 1.1 Composition is the public integration model

Do not make consumer entities extend package classes.

The following is explicitly rejected:

```php
class Patient extends HealthcarePatient
{
}
```

Reasons:

- PHP has single inheritance; consumers already have framework/application base classes;
- it would couple the package to Doctrine/entity hydration patterns indirectly;
- package constructor/API changes would propagate through every consumer inheritance hierarchy;
- application aggregates and shared healthcare semantics evolve for different reasons;
- persistence should remain entirely consumer-owned.

Instead, consumer aggregates should construct, expose or accept the shared immutable values:

```php
final class Patient
{
    public function healthcareIdentity(): ?PatientIdentity
    {
        // Build from persisted scalar/embedded state when enough data exists.
    }

    public function replaceHealthcareIdentity(PatientIdentity $identity): void
    {
        // Decompose into persistence-owned fields if required by the application.
    }
}
```

The package must make this style straightforward and complete enough that consumers do not reimplement the shared rules.

## 1.2 Identity is different from an application record reference

Do not replace every use of `Care\Entity\Patient` with `PatientIdentity` directly.

These concepts are different:

```text
PatientIdentity
= who the patient is according to shared identity semantics

PatientReference
= which patient record a clinical object refers to in the consuming context
```

The same distinction applies to practitioner and organization.

Typed references therefore become the dependency used by generic clinical objects.

## 1.3 Keep shared clinical entities

`Encounter`, `ServiceRequest`, `Observation`, `DiagnosticReport`, `Specimen`, `ClinicalDocument`, imaging objects, etc. remain shared domain objects.

They are generic healthcare resources rather than consumer application aggregates.

Their references to patient/practitioner/organization must however move away from the deleted `Care\Entity\*` classes and toward the new typed reference objects.

## 1.4 Do not refactor Medication in this change

`Medication`, `MedicationPresentation` and `ActiveSubstance` are not part of this architectural correction unless a compile-time dependency on removed Care entities requires it.

The first `pres` integration gave strong evidence about Patient/Practitioner/Organization boundaries, not enough evidence to redesign the medication aggregate again.

Keep this change focused.

---

# 2. Target public model

Target structure:

```text
src/
├── Care/
│   └── ValueObject/
│       ├── PractitionerIdentity.php
│       ├── OrganizationIdentity.php
│       ├── PractitionerRole.php
│       ├── PatientReference.php
│       ├── PractitionerReference.php
│       ├── OrganizationReference.php
│       ├── ProfessionCode.php
│       ├── SavoirFaireCode.php
│       ├── OrganizationCategoryCode.php
│       ├── ContactPoint.php
│       └── ...
│
├── Identity/
│   └── ValueObject/
│       ├── PatientIdentity.php
│       ├── StrictIdentityTraits.php
│       ├── HumanName.php
│       └── ...
│
├── Clinical/
│   └── Entity/
│       └── ... use typed references ...
│
└── Care/Entity/
    └── REMOVED
```

`Care\Entity` should disappear if no remaining class genuinely needs entity semantics after this refactor.

---

# 3. Preserve `PatientIdentity`

Keep the current `Healthcare\Identity\ValueObject\PatientIdentity` model and its RNIV/INS invariants.

It already has the correct architectural responsibility:

```text
StrictIdentityTraits
+ optional InsIdentifier
+ IdentityStatus
```

and factories:

```php
PatientIdentity::provisional($traits);
PatientIdentity::validated($traits);
PatientIdentity::recovered($traits, $ins);
PatientIdentity::qualified($traits, $ins);
```

Do not reintroduce persistence IDs, contacts, addresses, ownership or clinical state into `PatientIdentity`.

### Progressive patient records

A consumer may have an incomplete patient record before all strict RNIV traits are known.

That does **not** require weakening `PatientIdentity`.

The consumer may expose:

```php
public function healthcareIdentity(): ?PatientIdentity
```

and return `null` until the strict shared identity can truthfully be represented.

Do not invent placeholder COG codes, administrative gender or fake INS data to force construction.

---

# 4. Add `PractitionerIdentity`

Add:

```php
Healthcare\Care\ValueObject\PractitionerIdentity
```

Recommended API:

```php
final readonly class PractitionerIdentity
{
    public function __construct(
        public HumanName $name,
        public ?Rpps $rpps = null,
        public ?Adeli $adeli = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## Semantics

This represents the stable professional identity information common to healthcare applications.

It must **not** contain:

- database/application ID;
- organization membership;
- specialty/profession role;
- address;
- email/phone;
- active/inactive application state;
- credentials or permissions;
- FINESS.

FINESS identifies healthcare organizations/facilities, not an individual practitioner identity. If a current consumer stores FINESS on its Practitioner entity, that remains a consumer persistence/modeling concern until represented in an organization/role context.

## Invariants

- `HumanName` remains responsible for its own name invariants.
- RPPS, when present, must be represented by `Rpps`.
- ADELI, when present, must be represented by `Adeli`.
- Do not require RPPS/ADELI unconditionally: imported/manual/foreign practitioner records may be incomplete while still having a representable identity.
- Equality is full value equality: name + RPPS + ADELI.

Add focused tests.

---

# 5. Add `OrganizationIdentity`

Add:

```php
Healthcare\Care\ValueObject\OrganizationIdentity
```

Recommended API:

```php
final readonly class OrganizationIdentity
{
    public function __construct(
        public string $name,
        public ?Finess $finess = null,
        public ?Siren $siren = null,
        public ?Siret $siret = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## Invariants

- organization name must be non-blank;
- FINESS must use `Finess`;
- SIREN must use `Siren`;
- SIRET must use `Siret`;
- if both SIREN and SIRET are supplied, they must refer to the same legal entity.

To make the last invariant explicit and reusable, add an accessor to `Siret` if needed:

```php
public function siren(): Siren
```

It should derive the first nine digits and return the already validated `Siren`.

Then `OrganizationIdentity` can reject:

```text
siren != siret.siren()
```

with `InvalidValueObject` or a more specific existing domain exception if appropriate.

## Explicitly excluded

Do not put these into `OrganizationIdentity`:

- Address;
- ContactPoint;
- application organization type/configuration;
- practitioners collection;
- application lifecycle state.

Those may be composed independently by consumers.

Add tests for name validation, equality and SIREN/SIRET coherence.

---

# 6. Replace `SpecialtyCode` as the sole role expertise model

The first `pres` integration revealed a real shared-kernel gap:

- ordinal specialties use ANS TRE_R38 (`SM...`);
- exclusive competencies / compétences exclusives use TRE_R40 (`CEX...`).

The package must not encode CEX as TRE_R38.

## Add TRE_R40 code system

Add:

```php
CodeSystem::ansTreR40()
```

using the canonical ANS FHIR/NOS URI verified from the current authoritative ANS nomenclature.

Do not guess the URI: verify it before hard-coding.

## Introduce a broader savoir-faire concept

Recommended new type:

```php
Healthcare\Care\ValueObject\SavoirFaireCode
```

It wraps a generic `Coding` and provides at least:

```php
SavoirFaireCode::fromTreR38(...); // ordinal specialty
SavoirFaireCode::fromTreR40(...); // exclusive competence
```

Do not implement a closed enum of codes.

Unknown/historical/future valid coded systems must remain representable through the generic constructor if the current `Coding` architecture supports it.

Provide:

```php
equals()
sameCodeAs()
__toString()
```

### `SpecialtyCode`

Because `0.2.0` is intentionally breaking, prefer removing `SpecialtyCode` if its only purpose is now subsumed by `SavoirFaireCode`.

Do not keep two overlapping public concepts merely for backwards compatibility unless code inspection reveals a genuinely distinct semantic use.

Update all tests and README examples accordingly.

---

# 7. Refactor `PractitionerRole` into an immutable composable concept

Remove the current mutable entity:

```text
Healthcare\Care\Entity\PractitionerRole
```

It currently owns:

- an application-style string ID;
- back-references to `Practitioner` and `Organization` entities;
- mutable active state;
- collection registration side effects.

These are aggregate/persistence-shaped responsibilities and should not be part of the shared semantic object.

Add instead:

```php
Healthcare\Care\ValueObject\PractitionerRole
```

Recommended shape:

```php
final readonly class PractitionerRole
{
    /** @param list<SavoirFaireCode> $savoirFaire */
    public function __construct(
        public ProfessionCode $profession,
        public ?OrganizationIdentity $organization = null,
        public array $savoirFaire = [],
        public ?Period $validityPeriod = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## Why not `PractitionerRoleIdentity`

A practitioner role is primarily a contextual professional relationship, not the identity of a person or organization.

Keeping the public name `PractitionerRole` is clearer as long as it is moved to `ValueObject`, made immutable and stripped of application-record responsibilities.

A consumer can then compose:

```text
Application Practitioner
├── PractitionerIdentity
└── list<PractitionerRole>
```

without inheriting any package aggregate.

## Role invariants

- profession is required;
- organization is optional (e.g. independent role / incomplete source data);
- savoir-faire list may be empty;
- semantically duplicate savoir-faire values must be deduplicated or rejected consistently with package collection conventions;
- order must not affect value equality;
- validity period remains optional;
- no mutable `active` boolean in this shared value.

If a consumer imports an explicit `active` flag from RPPS or another source, that is source/application state. It should not make this shared value mutable.

Do not add persistence IDs.

---

# 8. Add typed record references

Add lightweight immutable reference values used by shared clinical resources.

## `PatientReference`

```php
final readonly class PatientReference
{
    public function __construct(
        public string $id,
        public ?PatientIdentity $identity = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## `PractitionerReference`

```php
final readonly class PractitionerReference
{
    public function __construct(
        public string $id,
        public ?PractitionerIdentity $identity = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## `OrganizationReference`

```php
final readonly class OrganizationReference
{
    public function __construct(
        public string $id,
        public ?OrganizationIdentity $identity = null,
    ) {}

    public function equals(self $other): bool {}
}
```

## Reference semantics

- `id` is the stable record identifier chosen by the consuming bounded context; it must be non-blank;
- do not require a database integer, UUID type or global URI;
- attached identity is an optional snapshot/context, not what makes the reference unique;
- **reference equality is based on `id`**, not on the attached identity value.

This means the same referenced record remains the same reference if its name or identity representation is updated later.

Do not introduce a generic inheritance hierarchy solely to share ten lines of reference code. Three explicit typed values are preferable unless implementation reveals a genuinely useful generic abstraction.

Add tests covering blank IDs, equality and different identity snapshots with the same reference ID.

---

# 9. Migrate generic clinical resources to typed references

Search the entire repository for imports/usages of:

```text
Healthcare\Care\Entity\Patient
Healthcare\Care\Entity\Practitioner
Healthcare\Care\Entity\Organization
Healthcare\Care\Entity\PractitionerRole
```

Replace dependencies according to semantic role, not mechanically by class name.

Expected examples:

## Encounter

Conceptually migrate:

```text
Patient                → PatientReference
Organization           → OrganizationReference
participating Practitioner[] → PractitionerReference[]
```

Collection deduplication should compare typed reference equality / IDs.

## ServiceRequest

Conceptually migrate:

```text
patient                → PatientReference
requester              → ?PractitionerReference
performerOrganization  → ?OrganizationReference
```

Do not require a full `PractitionerIdentity` merely to create a request if the consumer only has a stable practitioner record reference.

## DiagnosticReport

Conceptually migrate:

```text
patient                → PatientReference
performer              → ?PractitionerReference
performerOrganization  → ?OrganizationReference
```

## Observation / Specimen / ClinicalDocument / Imaging

Inspect each constructor/property and migrate patient/practitioner/organization dependencies to the corresponding typed reference.

Do not change unrelated clinical semantics while touching these files.

### Cross-resource consistency

Where current tests/scenarios rely on the same `Patient` object instance across request/observation/report/specimen, update the semantic check to use the same `PatientReference` / reference ID.

Do not introduce deep identity comparison as the link between resources.

---

# 10. Remove the old `Care\Entity` aggregate model

After all internal dependencies have migrated, remove:

```text
src/Care/Entity/Patient.php
src/Care/Entity/Practitioner.php
src/Care/Entity/Organization.php
src/Care/Entity/PractitionerRole.php
```

Remove the directory if empty.

Because the package is pre-1.0 and this is the planned `0.2.0` break, do not keep deprecated forwarding classes unless there is a concrete external compatibility requirement discovered during implementation.

Do **not** replace them with new pseudo-entities that simply repeat the same aggregate mistake under different names.

---

# 11. Test migration

Update all unit and scenario tests.

## New focused tests

Add tests for:

- `PractitionerIdentity`;
- `OrganizationIdentity`;
- SIREN/SIRET coherence;
- `Siret::siren()` if added;
- `SavoirFaireCode` TRE_R38/TRE_R40 factories;
- `PractitionerRole` equality and duplicate savoir-faire semantics;
- `PatientReference`;
- `PractitionerReference`;
- `OrganizationReference`.

## Existing scenario tests

Rewrite scenarios that currently construct:

```php
new Patient(...)
new Practitioner(...)
new Organization(...)
new PractitionerRole(...)
```

so that they construct identity/reference values instead.

Example target fixture style:

```php
$patientRef = new PatientReference(
    id: 'patient-1',
    identity: PatientIdentity::provisional($traits),
);

$practitionerIdentity = new PractitionerIdentity(
    new HumanName('DUPONT', ['Alice']),
    rpps: new Rpps('...'),
);

$practitionerRef = new PractitionerReference(
    id: 'practitioner-1',
    identity: $practitionerIdentity,
);

$organizationIdentity = new OrganizationIdentity(
    name: 'Example Hospital',
    finess: new Finess('...'),
);

$organizationRef = new OrganizationReference(
    id: 'organization-1',
    identity: $organizationIdentity,
);
```

Use synthetic identifiers that actually satisfy package validation.

Keep cross-module scenarios for laboratory and imaging; they are important regression tests for the new reference boundary.

---

# 12. README and public API documentation

Rewrite the README to make the architecture unambiguous.

## About section

Replace language suggesting that `healthcare-domain` provides universal application `Patient`/`Practitioner`/`Organization` aggregates.

Emphasize:

> The package owns reusable healthcare semantics and typed clinical resources. Consuming applications own persistence and application aggregates, and compose package identity/value objects instead of inheriting package entities.

## Module table

Target `Care` description should mention:

```text
PractitionerIdentity
OrganizationIdentity
PractitionerRole
PatientReference
PractitionerReference
OrganizationReference
ContactPoint
profession/savoir-faire/category codes
```

`PatientIdentity` remains under `Identity`.

## Usage examples

Add examples for:

1. composing `PatientIdentity` in an application patient record;
2. `PractitionerIdentity`;
3. `OrganizationIdentity`;
4. `PractitionerRole` using TRE_G15 + TRE_R38 or TRE_R40;
5. creating `PatientReference` / `PractitionerReference` for a `ServiceRequest` or `Observation`.

Remove examples based on the deleted `Care\Entity\*` classes.

## Design principles

Add an explicit principle:

### Composition over application-aggregate inheritance

```text
healthcare-domain defines semantic building blocks;
consumers define their aggregates and persistence;
clinical package resources refer to consumer records through typed references.
```

---

# 13. Consumer migration contract (`0.1.x` → `0.2.0`)

Document the breaking migration in the README/release notes.

Conceptual mapping:

```text
0.1.x                                   0.2.x
──────────────────────────────────      ─────────────────────────────────
Care\Entity\Patient                    consumer Patient aggregate
  identity                             + PatientIdentity
                                       + PatientReference when referenced

Care\Entity\Practitioner               consumer Practitioner aggregate
  name / rpps                          + PractitionerIdentity
  roles                                + PractitionerRole[]
                                       + PractitionerReference when referenced

Care\Entity\Organization               consumer Organization aggregate
  name / FINESS / SIREN / SIRET        + OrganizationIdentity
                                       + OrganizationReference when referenced

Care\Entity\PractitionerRole           Care\ValueObject\PractitionerRole

SpecialtyCode                          SavoirFaireCode (TRE_R38 / TRE_R40)
```

Consumers should not map their whole aggregate to a second package aggregate anymore.

Preferred consumer style after `0.2.0`:

```php
$identity = $patient->healthcareIdentity();
$reference = new PatientReference((string) $patient->getUuid(), $identity);
```

and when mutating shared identity semantics:

```php
$identity = PatientIdentity::qualified($traits, $ins);
$patient->replaceHealthcareIdentity($identity);
```

The package factory/VO is therefore the source of truth for the shared invariant; the consumer only decides how to persist it.

---

# 14. Explicit non-goals

Do not implement as part of this change:

- Doctrine embeddables or custom Doctrine types;
- Symfony integration;
- repositories;
- persistence interfaces;
- INSi API/client;
- Pro Santé Connect;
- FHIR serialization;
- patient merge/dedup workflows;
- application tenancy/ownership;
- prescription aggregates;
- user/accounts/security;
- audit/history;
- complete RPPS/Annuaire Santé reference datasets;
- a closed enum of professions/specialties/competencies;
- a redesign of Medication;
- speculative SGL-specific workflows.

Do not add framework convenience code to compensate for removing the old Care entities.

---

# 15. Implementation order

Implement in this order to keep the repository buildable in small coherent steps.

## Phase A — new semantic values

1. Add `PractitionerIdentity` + tests.
2. Add `Siret::siren()` if needed.
3. Add `OrganizationIdentity` + tests.
4. Verify current official ANS TRE_R40 canonical URI.
5. Add `CodeSystem::ansTreR40()`.
6. Add `SavoirFaireCode` + tests.
7. Refactor `PractitionerRole` into `Care\ValueObject\PractitionerRole` + tests.

Suggested commit boundary:

```text
feat: add composable care identity values
```

## Phase B — typed references

8. Add `PatientReference` + tests.
9. Add `PractitionerReference` + tests.
10. Add `OrganizationReference` + tests.

Suggested commit:

```text
feat: add typed healthcare record references
```

## Phase C — migrate clinical object graph

11. Replace `Care\Entity` dependencies in Clinical.
12. Replace them in Laboratory/Imaging where applicable.
13. Update cross-module scenarios.
14. Run the entire suite before deleting old classes.

Suggested commit:

```text
refactor: use typed references in clinical models
```

## Phase D — remove obsolete aggregate classes

15. Remove `Care\Entity\Patient`.
16. Remove `Care\Entity\Practitioner`.
17. Remove `Care\Entity\Organization`.
18. Remove old `Care\Entity\PractitionerRole`.
19. Remove `SpecialtyCode` if fully superseded.
20. Search repository for all obsolete namespaces/classes and ensure zero references remain.

Suggested commit:

```text
refactor!: remove application-shaped care entities
```

## Phase E — public documentation

21. Rewrite README examples/module descriptions.
22. Add `0.1.x → 0.2.0` migration notes.
23. Ensure no example suggests consumer inheritance or parallel aggregate mapping.

Suggested commit:

```text
docs: document composable 0.2 domain model
```

---

# 16. Quality gates

After every coherent phase run:

```bash
composer test
composer phpstan
composer cs
```

Before release, also run a clean installation from scratch.

The implementation must remain:

- PHP >= 8.2;
- framework-independent;
- zero runtime dependencies other than PHP;
- PHPStan level 8 clean;
- PSR-12 clean;
- fully unit-testable without database/kernel/network.

Do not silence PHPStan with broad ignores to make the refactor pass.

---

# 17. Acceptance criteria

The refactor is complete when:

- [ ] `PatientIdentity` remains the source of truth for RNIV/INS patient identity invariants.
- [ ] `PractitionerIdentity` exists and owns common practitioner identity semantics.
- [ ] `OrganizationIdentity` exists and owns common organization identity semantics.
- [ ] SIREN/SIRET coherence is enforced when both are present.
- [ ] TRE_R40 is represented truthfully.
- [ ] A shared savoir-faire concept can represent both TRE_R38 and TRE_R40 without lying about the code system.
- [ ] `PractitionerRole` is immutable/composable and no longer an application-shaped entity.
- [ ] `PatientReference`, `PractitionerReference`, and `OrganizationReference` exist.
- [ ] Typed reference equality is stable by record ID and independent of attached identity snapshots.
- [ ] Generic Clinical/Laboratory/Imaging objects no longer depend on `Care\Entity\Patient`, `Practitioner`, or `Organization`.
- [ ] `src/Care/Entity/Patient.php` is removed.
- [ ] `src/Care/Entity/Practitioner.php` is removed.
- [ ] `src/Care/Entity/Organization.php` is removed.
- [ ] the old mutable `Care\Entity\PractitionerRole` is removed.
- [ ] no consumer-style collection backreferences remain in Care identity/value objects.
- [ ] README examples use composition and typed references.
- [ ] a migration note from `0.1.x` is documented.
- [ ] PHPUnit passes.
- [ ] PHPStan passes.
- [ ] PHPCS passes.

---

# 18. Architectural litmus test

Before considering the work finished, verify that the following consumer designs are natural without changing the package:

```text
PRES
App\Entity\Patient
├── persistence / owner / prescriptions / user / history
└── ?PatientIdentity

App\Entity\Practitioner
├── persistence / login / signature / stamp
├── PractitionerIdentity
└── PractitionerRole[]

App\Entity\Organization
├── persistence / application configuration
└── OrganizationIdentity
```

and independently:

```text
SGL / LIS
PatientRecord
├── local dossier / stays / orders
└── ?PatientIdentity

PractitionerRecord
├── local permissions / routing
├── PractitionerIdentity
└── PractitionerRole[]

LaboratoryOrganization
├── analyzers / sites / accreditation
└── OrganizationIdentity
```

Both applications should be able to create the same shared clinical resources through:

```text
PatientReference
PractitionerReference
OrganizationReference
```

without inheriting package aggregates and without redefining French healthcare identity/professional constraints.

If a new abstraction is only useful to one consumer, keep it out of `healthcare-domain` until a second consumer demonstrates the shared need.
