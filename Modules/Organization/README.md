# Organization Module

**Phase:** F03 – F05  
**Owner:** `Modules/Organization`  
**Dependency graph position:** May depend on `Authorization` and `Audit` public surfaces. `AcademicCalendar` and `Staff` may depend on this module's public surface.

---

## Purpose

The Organization module owns:

- The GCV organization reference record.
- Institution-type classifications.
- The institution registry.
- The **feature-module catalogue** — configurable GCV business capabilities.
- **Institution-type feature rules** — the type-level mapping that declares which capabilities are required, optionally available, or unavailable for each institution type.

It provides the stable foundation that later modules (F06 institution-specific activation resolver, F17/F19 authorization) build upon.

---

## Terminology: physical modules vs feature modules

| Term | Meaning |
|---|---|
| **Physical module** | A Laravel package managed by `nwidart/laravel-modules`. Lives under `Modules/`. Examples: `Organization`, `Staff`. |
| **Feature module** | A configurable GCV business capability. Stored as rows in `feature_modules`. Examples: `academic_management`, `medical_services`. |

These are distinct concepts. The user-facing product may later label feature modules "Modules," but the codebase uses `FeatureModule` / `feature_modules` to prevent collision with Nwidart terminology.

---

## Records

### Organization

Represents the top-level organization. GCV is currently the only organization. The schema does not enforce a single-row database constraint.

- **Stable code:** `gcv`
- **Arabic name:** `قرية أطفال غزة` (stakeholder-approved)
- **Lifecycle:** active/inactive via `is_active`; no soft deletion; records remain queryable

### InstitutionType

Centrally controlled classification. Codes are rows, not enums.

**Approved codes:** `academy`, `university_space`, `medical_point`, `womens_center`, `storage_unit`

### Institution

An individual GCV location belonging to an organization and typed by an InstitutionType.

- Active institutions are returned by default (global `ActiveInstitutionScope`).
- Inactive institutions are reachable via `withoutGlobalScopes()`.

### FeatureModule

A configurable GCV business capability. `is_active` is lifecycle/configuration availability — not authorization and not proof that the feature has been implemented in code.

**Approved stable codes:**

| Code | English label |
|---|---|
| `staff_management` | Staff Management |
| `academic_management` | Academic Management |
| `asset_management` | Asset Management |
| `medical_services` | Medical Services |
| `womens_center_programs` | Women's Center Programs |
| `inventory_management` | Inventory Management |

Arabic labels are intentionally null until official approved translations are supplied.

### InstitutionTypeFeatureRule

The explicit rule governing a feature module's availability to an institution type. An explicit model rather than a featureless pivot because the relationship has behavior and will participate in F06 resolution.

---

## Rule semantics

Every institution-type/feature relationship has exactly one rule, or no rule:

| Rule | Stored value | Baseline state | F06 institution override may… |
|---|---|---|---|
| `Required` | `'required'` | Enabled | Not disable it |
| `DefaultEnabled` | `'default'` | Enabled | Disable it |
| `Allowed` | `'allowed'` | Disabled | Enable it |
| *(no rule)* | *(no row)* | Disabled | Not enable it |

The rule column is a bounded string, not a database ENUM. PHP-level validation via `FeatureModuleRule` enum is the primary enforcement boundary.

---

## Approved institution-type mapping matrix (F05)

| Feature | academy | university_space | medical_point | womens_center | storage_unit |
|---|:---:|:---:|:---:|:---:|:---:|
| `staff_management` | required | required | required | required | required |
| `academic_management` | required | required | — | — | — |
| `asset_management` | default | default | default | default | default |
| `medical_services` | — | — | allowed | — | — |
| `womens_center_programs` | — | — | — | required | — |
| `inventory_management` | — | — | — | — | required |

`—` = no rule = unavailable; F06 must not allow enabling it.

---

## Feature configuration vs authorization vs implementation

- **Configuration** (this module): declares which features are available to which types.
- **Authorization** (F17/F19): governs which staff may perform actions within an enabled feature.
- **Implementation**: a configured feature does not imply the business code has been built.

All three are required for a feature to be usable. This module provides only configuration.

---

## Stable codes vs translated display names

Stable codes (`code`) are machine identifiers:
- Set at creation; never modified by name-change or lifecycle actions.
- Used by application code to resolve records (e.g. `FeatureModule::where('code', 'academic_management')`).

Display names (`name_en`, `name_ar`) are human-readable labels:
- May be changed by administrators through change-name actions.
- Must never be used as matching keys in seeders or business logic.

---

## Lifecycle behavior

- **Active:** available for normal operation and new rule assignments.
- **Inactive:** preserved for historical reference; existing rules remain inspectable; no new rules may be assigned through ordinary application behavior.
- Deactivation does not delete institution-type rules.
- No global scope hides inactive `FeatureModule` or `InstitutionType` records.
- Active institutions are hidden by default via `ActiveInstitutionScope` (bypass with `withoutGlobalScopes()`).

---

## Seeding behavior

All seeders are idempotent:

- Create missing records.
- Preserve administrator-edited display names and lifecycle state.
- Do not silently overwrite existing institution-type rules that an administrator may have changed.
- Use stable codes for matching; display names are never matching keys.

**Run order** (enforced in `DatabaseSeeder`):
1. `OrganizationReferenceSeeder`
2. `InstitutionTypeReferenceSeeder`
3. `FeatureModuleReferenceSeeder`
4. `InstitutionTypeFeatureRuleReferenceSeeder`

---

## Baseline rule interpreter

`InstitutionTypeRuleInterpreter` answers type-level questions:

```php
$interpreter->isBaselineEnabled($type, $feature); // true for required/default
$interpreter->canBeDisabled($type, $feature);      // true only for default
$interpreter->canBeEnabled($type, $feature);       // true only for allowed
$interpreter->isUnavailable($type, $feature);      // true when no rule row exists
$interpreter->ruleFor($type, $feature);            // ?FeatureModuleRule
```

This interpreter is **not** an authorization check and does not apply institution-specific overrides. The complete institution-level resolver belongs to F06.

---

## Authorization boundary

No HTTP endpoints are exposed through F05. All application actions are internal services for future authorized callers:

**FeatureModule actions:** `CreateFeatureModule`, `ChangeFeatureModuleName`, `ActivateFeatureModule`, `DeactivateFeatureModule`

**Rule actions:** `AssignInstitutionTypeRule`, `RemoveInstitutionTypeRule`

Future HTTP callers must go through the F17/F19 policy kernel. No allow-all bypass may be added.

---

## Public surface

Per `docs/MODULE_CONVENTIONS.md`, only these namespaces may be accessed by other modules:

- `Modules\Organization\Actions\`
- `Modules\Organization\Contracts\` *(none in F05)*
- `Modules\Organization\Data\`
- `Modules\Organization\Enums\`
- `Modules\Organization\Events\` *(none in F05)*

Services (`app/Services/`) are currently internal. If `InstitutionTypeRuleInterpreter` is needed by another module, promote it to a Contract.

---

## Coming in later phases

- **F06** — Institution-specific feature activation overrides and the effective resolution engine (decision gate required).
- **F17/F19** — Policy kernel; HTTP callers use it before invoking any management action.
