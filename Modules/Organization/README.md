# Organization Module

**Phase:** F03  
**Owner:** `Modules/Organization`  
**Dependency graph position:** May depend on `Authorization` and `Audit` public surfaces. `AcademicCalendar` and `Staff` may depend on this module's public surface.

---

## Purpose

The Organization module owns the reference model for GCV as an organization and for institution types. It provides the stable foundation that later modules (F04 Institution registry, F05 module activation) build upon.

## Records

### Organization

Represents the top-level organization. GCV is currently the only organization. The schema is future-capable and does not enforce a database-level single-row constraint.

- **Internal primary key:** unsigned auto-incrementing BIGINT (`id`)
- **Stable code:** unique string assigned at creation; never changed afterwards
- **Display names:** `name_en` (required), `name_ar` (nullable — see note below)
- **Lifecycle:** `is_active` boolean, active by default
- **No soft deletion:** deactivating preserves the record and all historical references

**Pending:** The official approved Arabic name for GCV has not yet been supplied. The `name_ar` field remains `null` in the reference seeder until it is provided and approved. Do not invent organizational terminology.

### InstitutionType

A centrally controlled classification that will determine which modules are available to an institution (F05). Types are stored as rows, not PHP or database enums, so future types may be added without a schema change.

**Approved stable codes (F03):**

| Code | English label |
|---|---|
| `academy` | Academy |
| `university_space` | University Space |
| `medical_point` | Medical Point |
| `womens_center` | Women's Center |
| `storage_unit` | Storage Unit |

Arabic labels are intentionally null in the seeder until official approved translations are supplied.

## Stable codes versus translated display names

Stable codes (`code`) are machine identifiers. They are:
- Set at creation through `CreateOrganization` or `CreateInstitutionType`.
- Never modified by name-change or lifecycle actions.
- Used by application code to resolve known records (e.g. `Organization::where('code', 'gcv')`).

Display names (`name_en`, `name_ar`) are human-readable labels. They:
- May be changed by administrators through `ChangeOrganizationName` / `ChangeInstitutionTypeName`.
- Should never be used as machine identifiers or foreign-key values.

## Lifecycle behavior

- **Active:** normal operation.
- **Inactive:** record is preserved; all historical references remain valid and queryable. No record is deleted during deactivation.

Inactive records are intentionally not globally scoped out of queries. Consumers that need only active records must add an explicit `where('is_active', true)` filter.

## Seeding behavior

`OrganizationReferenceSeeder` and `InstitutionTypeReferenceSeeder` are idempotent:

- They create records that do not yet exist.
- They **do not** overwrite existing display names or lifecycle state.
- They are safe to run repeatedly (e.g. after a fresh migration).

This means administrator edits to labels or lifecycle state are preserved across subsequent seeder runs.

## Authorization boundary

F03 has no public management HTTP endpoints. The application actions are internal services for future authorized callers:

- `CreateOrganization`
- `ChangeOrganizationName`
- `ActivateOrganization`
- `DeactivateOrganization`
- `CreateInstitutionType`
- `ChangeInstitutionTypeName`
- `ActivateInstitutionType`
- `DeactivateInstitutionType`

Future HTTP callers must go through the F17/F19 policy kernel. These actions must not be called from an allow-all bypass or unauthenticated context.

## Public surface

Per `docs/MODULE_CONVENTIONS.md`, only these namespaces may be accessed by other modules:

- `Modules\Organization\Actions\` — stable application operations
- `Modules\Organization\Contracts\` — interfaces and ports (none in F03)
- `Modules\Organization\Data\` — immutable DTOs/value messages
- `Modules\Organization\Events\` — published domain/application events (none in F03)

Other namespaces (models, factories, seeders, etc.) are internal to this module.

## Coming in later phases

- **F04** — Institution registry: individual institutions belonging to GCV and typed by `InstitutionType`.
- **F05** — Module catalogue and type rules: which modules each institution type may activate.
- **F06** — Institution activation resolver: per-institution module activation overrides (decision gate required).
