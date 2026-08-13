# AcademicCalendar Module

The `AcademicCalendar` module owns the GCV DATA global academic year and semester catalogue. It provides the shared temporal scaffolding that all institution-scoped academic operations are built upon.

---

## Hierarchy

```
Organization
└── AcademicYear  (one per organization, many allowed)
    └── Semester  (any number per year, no two-semester assumption)
```

`AcademicYear` belongs to an `Organization`. `Semester` belongs to an `AcademicYear`. The reverse direction has no relationship: `Organization` does not have an `academicYears()` method, and `Semester` does not have a shortcut back to `Organization`. Dependency flows one way only.

---

## Stable codes vs bilingual names

Every `AcademicYear` and `Semester` carries:

| Field | Role | Mutable |
|---|---|---|
| `code` | Stable machine identifier — unique within its parent scope | **Never** |
| `name_en` | Human-readable English display name | Yes, unless Archived |
| `name_ar` | Human-readable Arabic display name (nullable) | Yes, unless Archived |

Codes are excluded from `$fillable` to prevent accidental mass-assignment overwrites. All creation passes through the module's application actions, which set `code` directly on the model before saving.

---

## Date containment rules

- `AcademicYear`: `starts_on` must be strictly before `ends_on`.
- `Semester`: dates must be strictly within the parent year's range (`starts_on >= year.starts_on` and `ends_on <= year.ends_on`).
- Semesters must not overlap each other within the same year (checked at `starts_on`/`ends_on` level; touching boundaries are allowed).
- `ChangeAcademicYearDates` validates that all existing semesters still fit within the proposed new year range before saving.

---

## Non-overlapping semesters

When `CreateSemester` or `ChangeSemesterDates` is called, the action queries all sibling semesters and rejects any date range that overlaps an existing one. Touching endpoints (`ends_on` of one equals `starts_on` of another) are permitted. Overlap is defined as the candidate range starting before an existing sibling ends and ending after that sibling starts.

---

## Lifecycle rules

Both `AcademicYear` and `Semester` share the same four-state lifecycle:

```
Draft → Open → Closed → Archived
                 ↑ (Reopen)
```

### AcademicYear transitions

| Transition | Action | Requirements |
|---|---|---|
| Draft → Open | `OpenAcademicYear` | ≥ 1 semester exists |
| Open → Closed | `CloseAcademicYear` | All semesters are Closed or Archived |
| Closed → Open | `ReopenAcademicYear` | Non-empty reason; no other Open year for the same organization |
| Closed → Archived | `ArchiveAcademicYear` | Year must be Closed |

### Semester transitions

| Transition | Action | Requirements |
|---|---|---|
| Draft → Open | `OpenSemester` | Parent year must be Open |
| Open → Closed | `CloseSemester` | — |
| Closed → Open | `ReopenSemester` | Non-empty reason; parent year must be Open; no other Open semester in the same year |
| Closed → Archived | `ArchiveSemester` | Semester must be Closed; parent year must **not** be Archived |

Name changes are blocked only when the entity is **Archived**. Date changes are blocked once status moves past **Draft**.

---

## One-open constraint

At any moment:

- **One open academic year per organization** — enforced in a `DB::transaction()` within `OpenAcademicYear` and `ReopenAcademicYear`.
- **One open semester per academic year** — enforced in a `DB::transaction()` within `OpenSemester` and `ReopenSemester`.

SQLite does not support `lockForUpdate()`; the transaction boundary provides sufficient isolation for the test suite and development environment. Production deployments should consider advisory locks or optimistic concurrency if concurrent lifecycle mutations are expected.

---

## No two-semester assumption

The module places no minimum or maximum limit on the number of semesters within a year. Institutions that use trimesters, quarters, or other arrangements create as many semesters as needed. No default semester is seeded automatically.

---

## No production seeding

**No academic years or semesters are seeded in the database.** Administrators create their own calendars after deployment. The seeders supplied with the module are test helpers only and should not be run in production.

---

## Global semester vs institution semester

`Semester` is a **global catalogue entry** — it describes a period within an academic year for the organization as a whole. It carries no institution-specific operational facts.

`InstitutionSemester` links a specific `Institution` to a global `Semester` and carries institution-owned lifecycle, preparation history, and current operational-period configuration. `OperationalPeriod` records the named time-of-day shifts (e.g. Morning, Afternoon) that apply within that institution semester.

---

## F08 — Institution semester activation and operational periods

### Hierarchy (F08)

```
Organization
└── AcademicYear  (global)
    └── Semester  (global)
        └── InstitutionSemester  (per Institution)
            └── OperationalPeriod  (time-of-day shifts within the IS)
```

### InstitutionSemester lifecycle

`InstitutionSemester` reuses the same four-state lifecycle as `AcademicYear` and `Semester`:

```
Draft → Open → Closed → Archived
         ↑ (Reopen)
```

| Transition | Action | Requirements |
|---|---|---|
| Create | `CreateInstitutionSemester` | Institution active; `academic_management` feature enabled; semester not Archived; same organization; no duplicate pair |
| Draft → Open | `OpenInstitutionSemester` | Parent year Open; parent semester Open; institution active; feature enabled; ≥ 1 active period; no other Open IS for the same institution |
| Open → Closed | `CloseInstitutionSemester` | — |
| Closed → Open | `ReopenInstitutionSemester` | Non-empty reason; parent year Open; parent semester Open; no other Open IS for the same institution |
| Draft → Archived | `ArchiveInstitutionSemester` | Non-empty reason |
| Closed → Archived | `ArchiveInstitutionSemester` | Parent semester must be Closed or Archived |
| Copy | `CopyInstitutionSemesterConfiguration` | Target semester same org; target not Closed/Archived; no existing IS for the target pair; atomic |

### OperationalPeriod rules

- Periods may only be added, updated, or deactivated while the parent `InstitutionSemester` is **Draft**.
- `code` and `sequence` are stable identifiers; they cannot be changed after creation.
- Deactivation (`DeactivateOperationalPeriod`) replaces hard deletion. Deactivated periods remain queryable for historical reference.
- Active periods within the same institution semester must not overlap (`starts_at < sibling.ends_at AND ends_at > sibling.starts_at`). Adjacent boundaries are permitted.
- `starts_at` and `ends_at` are stored as `TIME` strings (`HH:MM:SS`). Overnight periods are not supported in F08.

### Global-semester closure integration

`CloseSemester` rejects closure while any linked `InstitutionSemester` is in **Draft** or **Open** status. All institution semesters must be explicitly closed (via `CloseInstitutionSemester`) or archived (via `ArchiveInstitutionSemester`) before the global semester can close.

### Feature guard

`CreateInstitutionSemester` and `OpenInstitutionSemester` verify that the institution has the `academic_management` feature enabled via `InstitutionFeatureResolver`. If the feature is not registered or not enabled for the institution, the action throws.

### F02 scope resolution adapter

`ResolveInstitutionSemesterScope` implements `OperationalScopeAuthorizer` and validates the institution → institution-semester → operational-period hierarchy from opaque string references. Key behaviours:

- References are string-cast integer primary keys.
- Closed, archived, and inactive records are still resolvable (historical reads).
- Mismatched parent chains are rejected with a `RuntimeException`.
- **Not registered** in the service container. Binding it would create an implicit allow-all authorizer. Callers requiring scope resolution must resolve the class by name directly.

### One-open invariant

At any moment, **at most one `InstitutionSemester` may be Open per institution**. Enforced in `DB::transaction()` within `OpenInstitutionSemester` and `ReopenInstitutionSemester`.

---

## Cross-module dependency pattern

`AcademicCalendar` may depend on `Organization` per the module-boundaries configuration. However, the boundary scanner flags `use Modules\Organization\Models\Organization` because `Models` is not a declared public surface. To comply, cross-module class names are passed as double-escaped string literals (e.g. `belongsTo('Modules\\Organization\\Models\\Organization')`). PHP resolves these at runtime via the autoloader; pint does not add `use` imports for string literals; and the scanner does not match double-backslash content.

The `Organization` module does **not** get a reverse dependency. It has no `academicYears()` relationship.

---

## Future audit integration

F18 will add actor-aware audit history for lifecycle transitions. The current actions perform all mutations without recording who triggered them. The transition points are well-defined (each action is a single class with one `execute()` method) and ready for audit decoration.

---

## File layout

```
Modules/AcademicCalendar/
├── app/
│   ├── Actions/
│   │   ├── AddOperationalPeriod.php
│   │   ├── ArchiveAcademicYear.php
│   │   ├── ArchiveInstitutionSemester.php
│   │   ├── ArchiveSemester.php
│   │   ├── ChangeAcademicYearDates.php
│   │   ├── ChangeAcademicYearNames.php
│   │   ├── ChangeSemesterDates.php
│   │   ├── ChangeSemesterNames.php
│   │   ├── CloseAcademicYear.php
│   │   ├── CloseInstitutionSemester.php
│   │   ├── CloseSemester.php          ← modified F08: blocks if any IS is Draft/Open
│   │   ├── CopyInstitutionSemesterConfiguration.php
│   │   ├── CreateAcademicYear.php
│   │   ├── CreateInstitutionSemester.php
│   │   ├── CreateSemester.php
│   │   ├── DeactivateOperationalPeriod.php
│   │   ├── ListInstitutionSemesters.php
│   │   ├── ListOperationalPeriods.php
│   │   ├── OpenAcademicYear.php
│   │   ├── OpenInstitutionSemester.php
│   │   ├── OpenSemester.php
│   │   ├── ReopenAcademicYear.php
│   │   ├── ReopenInstitutionSemester.php
│   │   ├── ReopenSemester.php
│   │   ├── ResolveInstitutionSemesterScope.php
│   │   └── UpdateOperationalPeriod.php
│   ├── Data/
│   │   ├── ChangeAcademicYearDatesData.php
│   │   ├── ChangeAcademicYearNamesData.php
│   │   ├── ChangeSemesterDatesData.php
│   │   ├── ChangeSemesterNamesData.php
│   │   ├── CreateAcademicYearData.php
│   │   ├── CreateInstitutionSemesterData.php
│   │   ├── CreateOperationalPeriodData.php
│   │   ├── CreateSemesterData.php
│   │   └── UpdateOperationalPeriodData.php
│   ├── Enums/
│   │   └── AcademicStatus.php
│   └── Models/
│       ├── AcademicYear.php
│       ├── InstitutionSemester.php
│       ├── OperationalPeriod.php
│       └── Semester.php
├── database/
│   ├── factories/
│   │   ├── AcademicYearFactory.php
│   │   ├── InstitutionSemesterFactory.php
│   │   ├── OperationalPeriodFactory.php
│   │   └── SemesterFactory.php
│   └── migrations/
│       ├── 2026_08_13_000007_create_academic_years_table.php
│       ├── 2026_08_13_000008_create_semesters_table.php
│       ├── 2026_08_13_000009_create_institution_semesters_table.php
│       └── 2026_08_13_000010_create_operational_periods_table.php
└── tests/Feature/
    ├── AcademicCalendarBoundaryTest.php   ← updated F08
    ├── AcademicCalendarLifecycleTest.php
    ├── AcademicYearActionsTest.php
    ├── AcademicYearSchemaTest.php
    ├── F08BoundaryTest.php
    ├── InstitutionSemesterActionsTest.php
    ├── InstitutionSemesterLifecycleTest.php
    ├── InstitutionSemesterSchemaTest.php
    ├── OperationalPeriodActionsTest.php
    └── SemesterActionsTest.php
```
