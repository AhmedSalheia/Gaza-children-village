# Organization Module

**Phases:** F03 – F06  
**Owner:** `Modules/Organization`  
**Dependency graph position:** May depend on `Authorization` and `Audit` public surfaces. `AcademicCalendar` and `Staff` may depend on this module's public surface.

---

## Purpose

The Organization module owns:

- The GCV organization reference record.
- Institution-type classifications.
- The institution registry.
- The **feature-module catalogue** — configurable GCV business capabilities.
- **Institution-type feature rules** — type-level mapping of which capabilities are required, allowed, or unavailable for each institution type.
- **Institution-specific feature overrides** — explicit per-institution departures from the type baseline.
- The **effective feature resolver** — combines type rule + institution override into a single authoritative answer.

---

## Terminology: physical modules vs feature modules

| Term | Meaning |
|---|---|
| **Physical module** | A Laravel package managed by `nwidart/laravel-modules`. Lives under `Modules/`. Examples: `Organization`, `Staff`. |
| **Feature module** | A configurable GCV business capability. Stored as rows in `feature_modules`. Examples: `academic_management`, `medical_services`. |

These are distinct concepts. The user-facing product may later label feature modules "Modules," but the codebase uses `FeatureModule` / `feature_modules` to prevent collision with Nwidart terminology.

---

## Domain records

### Organization

Represents the top-level organization. GCV is currently the only organization.

- **Stable code:** `gcv` | **Arabic name:** `قرية أطفال غزة` (stakeholder-approved)
- Lifecycle: `is_active`; no soft deletion; records remain queryable

### InstitutionType

Centrally controlled classification. Codes are rows, not enums.

**Approved codes:** `academy`, `university_space`, `medical_point`, `womens_center`, `storage_unit`

### Institution

An individual GCV location belonging to an organization and typed by an InstitutionType.

- Active institutions are returned by default (`ActiveInstitutionScope` global scope).
- Inactive institutions: bypass with `withoutGlobalScopes()`.

### FeatureModule

A configurable GCV business capability. `is_active` = lifecycle/configuration availability; not authorization and not proof of implementation.

**Approved stable codes:** `staff_management`, `academic_management`, `asset_management`, `medical_services`, `womens_center_programs`, `inventory_management`

### InstitutionTypeFeatureRule

The type-level rule governing a feature module's availability to an institution type.

### InstitutionFeatureOverride

An explicit per-institution departure from the type baseline. Only meaningful rows are stored:

| Type rule | Permitted override |
|---|---|
| `DefaultEnabled` | `is_enabled = false` (disable an on-by-default feature) |
| `Allowed` | `is_enabled = true` (enable an off-by-default feature) |
| `Required` | **Rejected** — required features cannot be disabled |
| No rule | **Rejected** — unavailable features cannot be enabled |

---

## Resolution semantics

Every institution/feature pair resolves to one of these sources:

| Source | Enabled? | Override? | Institution may |
|---|:---:|:---:|---|
| `required` | ✓ | No | Nothing |
| `type_default` | ✓ | No | Disable (creates override) |
| `institution_override` (from DefaultEnabled) | ✗ | Yes | Clear override (restores enabled) |
| `allowed_but_disabled` | ✗ | No | Enable (creates override) |
| `institution_override` (from Allowed) | ✓ | Yes | Clear override (restores disabled) |
| `unavailable` | ✗ | No | Nothing |
| `feature_inactive` | ✗ | No | Nothing |
| `institution_inactive` | ✗ | No | Nothing |

**Clearing an override** removes the row and restores the type-derived baseline — it does not modify the institution-type rule or the FeatureModule.

---

## Approved institution-type mapping matrix (F05 baseline)

| Feature | academy | university_space | medical_point | womens_center | storage_unit |
|---|:---:|:---:|:---:|:---:|:---:|
| `staff_management` | required | required | required | required | required |
| `academic_management` | required | required | — | — | — |
| `asset_management` | default | default | default | default | default |
| `medical_services` | — | — | allowed | — | — |
| `womens_center_programs` | — | — | — | required | — |
| `inventory_management` | — | — | — | — | required |

`—` = no rule = unavailable; F06 cannot enable it via override.

---

## Feature configuration vs authorization vs implementation

- **Configuration** (this module): declares which features are available and enabled for which institutions.
- **Authorization** (F17/F19): governs which staff may perform actions within an enabled feature.
- **Implementation**: a configured feature does not imply the business code has been built.

All three are required for a feature to be usable. This module provides configuration only.

---

## Resolver API

```php
use Modules\Organization\Services\InstitutionFeatureResolver;

$resolver = new InstitutionFeatureResolver;

// Resolve one feature
$result = $resolver->resolve($institution, $feature);
$result->isEnabled();           // effective enabled state
$result->isAvailable();         // type has a rule (not unavailable/inactive)
$result->source();              // ResolutionSource enum case
$result->reasonKey();           // stable string for logs ('required', 'type_default', …)
$result->canBeEnabled();        // institution may create an enable override
$result->canBeDisabled();       // institution may create a disable override
$result->hasOverride();         // explicit override row exists

// Resolve by stable feature code (explicit, separately named)
$result = $resolver->resolveByCode($institution, 'academic_management');

// Return only effectively enabled, active feature models (3 queries, N+1 safe)
$features = $resolver->enabledFor($institution);

// Return all resolution results including disabled/unavailable (3 queries, N+1 safe)
$results = $resolver->resolveAll($institution);
```

**Do not** mix model, numeric ID, and code arguments through one method. Use `resolve()` for models and `resolveByCode()` for codes.

---

## Override action API

```php
use Modules\Organization\Actions\SetInstitutionFeatureOverride;
use Modules\Organization\Actions\ClearInstitutionFeatureOverride;
use Modules\Organization\Data\SetInstitutionFeatureOverrideData;

// Set a meaningful override (validates all rejection cases; runs in DB transaction)
$override = (new SetInstitutionFeatureOverride)->execute(
    $institution,
    $feature,
    new SetInstitutionFeatureOverrideData(isEnabled: false, reason: 'Not needed here')
);

// Clear an override (no-op if absent; runs in DB transaction)
(new ClearInstitutionFeatureOverride)->execute($institution, $feature);
```

---

## Override mutation prerequisites (F17+)

`reason` is temporarily nullable. Management UI **must not** expose override mutation until:

1. Actor tracking (`who changed this`) is implemented.
2. An explicit permission check gates the action (F17/F19 policy kernel).
3. Audit module integration records every mutation with actor reference and timestamp.

At that point `reason` should be made non-nullable and the constraint enforced in the action.

---

## Seeding behavior

All seeders are idempotent:

- Create missing records.
- Preserve administrator-edited display names and lifecycle state.
- Do not silently overwrite existing institution-type rules.
- Do not touch institution-specific override rows.

**Run order** (enforced in `DatabaseSeeder`):
1. `OrganizationReferenceSeeder`
2. `InstitutionTypeReferenceSeeder`
3. `FeatureModuleReferenceSeeder`
4. `InstitutionTypeFeatureRuleReferenceSeeder`

No override rows are seeded. All institutions inherit the type-derived baseline on first boot.

---

## Stable codes vs translated display names

Stable codes are machine identifiers used in all application logic. Display names (`name_en`, `name_ar`) are administrator-editable labels. **Never use display names as lookup keys** in seeders, resolvers, or business logic.

---

## Lifecycle behavior

| Entity | Inactive behavior |
|---|---|
| `FeatureModule` | Queries return `feature_inactive`; existing type rules and overrides remain inspectable; no new overrides may be created |
| `Institution` | Queries return `institution_inactive`; configuration remains inspectable for administration; no new overrides may be created |
| `InstitutionType` | Existing institutions continue resolving against their assigned type's rules; type inactivity does not erase those rules |

---

## Foreign key strategy

`institution_feature_overrides` uses **RESTRICT** FKs to `institutions` and `feature_modules`. Deleting an institution or feature throws a database exception rather than silently cascade-deleting historical configuration. Deactivation (not deletion) is the approved lifecycle pattern.

---

## Public surface

Per `docs/MODULE_CONVENTIONS.md`, other modules may only access:

- `Modules\Organization\Actions\`
- `Modules\Organization\Contracts\` *(none yet)*
- `Modules\Organization\Data\`
- `Modules\Organization\Enums\`
- `Modules\Organization\Events\` *(none yet)*

Services (`app/Services/`) are currently internal. If `InstitutionFeatureResolver` is needed by another module, promote it to a Contract.

---

## Coming in later phases

- **F17/F19** — Policy kernel; HTTP callers use it before invoking any management or override action.
- **Audit module integration** — Every override mutation must be audited with actor reference, timestamp, and non-nullable reason before management UI is released.
- **F08** — `OperationalScopeAuthorizer` implementation; institution lookup consumed via this module's public surface.
