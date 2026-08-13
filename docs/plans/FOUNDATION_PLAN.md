# GCV DATA Foundation Release Implementation Plan

**Status:** F01 complete · F02 complete · F03 complete · F04 complete · F05 complete · F06 complete · F07 complete · F08 complete — F09 and later are planning only.
**Prepared from:** `AGENTS.md` and the complete canonical `docs/SYSTEM_SPECIFICATION.md` (version 0.2, 12 August 2026).
**Approved architecture decision:** Use `nwidart/laravel-modules` 13.x, the release line compatible with Laravel 13 and PHP 8.3+, for package-managed modules under the repository-root `Modules/` directory.

## 1. Scope and planning constraints

This plan covers only the shared Foundation Release:

- Laravel modular-monolith structure and engineering conventions.
- Separate admin, staff, and guardian authentication boundaries.
- GCV organization, institution types, institutions, and module activation.
- Academic years, semesters, institution semesters, and operational periods.
- Permanent people identities, identifiers, and contact points (not student enrolment or guardian relationships).
- Staff profiles, positions, institutional history, and period scopes (not teaching/course assignments).
- Authorization and audit foundations.
- Arabic/English localization and RTL/LTR foundations.
- Initial centralized design tokens.

Explicitly excluded are student enrolment and placement, guardian-student relationship workflows, classes and subjects, teaching assignments, attendance, assessments, marks and results, publication, documents, formal requests, medical records, inventory/assets, women's-center courses and beneficiaries, notifications, imports/exports, offline synchronization, and the full Admin Portal. References to these domains below identify extension points or security boundaries only; they do not authorize implementation.

No entity name in this document is a final table name. No suggested state catalogue, uniqueness rule, package, or account topology that corresponds to a listed unresolved decision may be implemented until its decision gate is approved.

## 2. Repository assessment

The repository is a nearly stock Laravel 13.17 application on PHP 8.3 with Livewire 4.4, Vite 8, Tailwind CSS 4, Pest 5, and Laravel Pint. It currently has:

- A single generic `App\Models\User`, `web` guard, `users` provider, password broker, reset-token store, and sessions table.
- Only the Laravel cache, queue, users, password-reset, and session migrations.
- A single welcome route/view; no portal routes, domain modules, policies, middleware, localization files, or audit infrastructure.
- One CSS entry point containing Tailwind's default `Instrument Sans`; none of the GCV semantic tokens are implemented.
- The supplied dark-background PNG logo at `resources/brand/gcv-logo-dark.png`.
- Default example tests only; no domain or security test harness.
- No CI configuration visible in the repository.

Implications:

1. The default `users` migration and factory are scaffolding, not an approved account model. Avoid building data on them before account topology is decided.
2. The modular boundaries, scoped authorization context, portal route/session separation, audit schema, localization conventions, and semantic design system all need deliberate foundations.
3. MySQL/MariaDB is the preferred production direction, but the exact engine/version and CI test database are not confirmed. Database-specific constraints must therefore wait for a decision.

## 3. Cross-cutting architectural proposal

Use a modular Laravel monolith with one deployable application and one relational database. `nwidart/laravel-modules` manages bounded modules under the repository-root `Modules/` directory. Each module owns its domain application code, routes, migrations, factories, seeders, translations, views where applicable, and tests. The implemented logical structure begins as:

```text
Modules/
  Accounts/
  Organization/
  AcademicCalendar/
  People/
  Staff/
  Authorization/
  Audit/
routes/{admin,staff,guardian}.php
tests/{Architecture,Feature,Unit}/...
```

Each module exposes deliberate public contracts, actions, DTOs, or events. A module must not call another module's controllers, Livewire/UI components, internal HTTP layer, or other implementation classes. The root Laravel application remains thin and contains only genuinely cross-cutting framework integration. Livewire is the preferred browser UI approach. Composer discovers each module's own autoload rules using the package-required merge plugin; the obsolete root `Modules\\` PSR-4 mapping is forbidden. Dependency direction and public namespaces are documented in `docs/MODULE_CONVENTIONS.md` and enforced by F01 architecture tests. Transactions protect multi-record state changes. Foreign keys and indexes enforce structural integrity; policies, query scopes, services, and database constraints jointly enforce business scope.

The Authorization module owns an immutable `OperationalContext` carrying an explicit portal/actor reference plus a resolved institution, institution semester, and optional period. External values first enter an untrusted scope DTO and can become context only through the module's resolver/authorizer contract. A Laravel-scoped store is empty by default and prevents lifecycle leakage. The module deliberately provides no default authorizer. Opaque transport references do not choose a database key strategy. See `docs/OPERATIONAL_CONTEXT.md`.

## 4. Area plans

### 4.1 Laravel application structure

#### Requirements

- Build a modern modular Laravel monolith backed by a relational database, preferably MySQL/MariaDB.
- Implement bounded modules/workflows incrementally; keep domain logic out of controllers and UI components.
- Use explicit foreign keys/indexes, transactions for multi-record workflows, policies for protected resources, and stable application services/APIs.
- Never create tables per institution, year, or semester; never duplicate permanent people by semester.
- Prepare shared services for authentication, authorization, institution/semester context, audit, localization, queues, and settings.
- Keep the web implementation compatible with a possible future offline client without implementing synchronization now.
- Use synthetic data only in fixtures/tests.

#### Blocking unresolved decisions

- Final schema/table names and database engine/version.
- The module convention is resolved: use `nwidart/laravel-modules` 13.x and root `Modules/` packages.
- Livewire is the preferred browser UI approach; exact screen composition remains bounded to later PRs.
- CI provider, supported PHP/database matrix, deployment environments, queue backend, hosting, backup, recovery, and retention.
- Offline/synchronization architecture; it must remain excluded.

#### Proposed entities and relationships

No business entity is needed solely for structure. Shared value objects/contracts should include stable identifiers, lifecycle transitions, `OperationalContext`, actor identity, clock, and audit metadata. A controlled settings abstraction may be designed later, but a generic key/value settings table should not be created until ownership, type validation, localization, and sensitivity rules are agreed.

#### Authorization boundaries

- Portal route groups use their own guard and middleware stack.
- Every protected resource is checked by a policy/action and scoped query; controllers do not accept arbitrary organization/institution/semester identifiers as authority.
- Central read scope and institution-owned mutation scope are distinct capabilities.
- CLI/jobs/imports must carry an explicit system or initiating actor and explicit scope; they do not bypass policies implicitly.

#### Small PRs and tests

**PR F01 — Repository conventions and test harness**

- Install/configure `nwidart/laravel-modules` 13.x with per-module Composer files and create only the Accounts, Organization, AcademicCalendar, People, Staff, Authorization, and Audit shells.
- Document module ownership, deliberate public surfaces, dependency direction, default-deny authorization, Livewire preference, synthetic fixture policy, and supported commands.
- Split portal route files with placeholder route groups only; add architecture tests and CI-ready test configuration.
- Do not create domain tables or portal features.

Tests:

- Architecture tests prevent module code from depending on portal controllers/views.
- Architecture tests prevent cross-module internal HTTP access, reject root `App\\Models` domain growth, enforce public dependency surfaces/direction, and reject later-release modules.
- Undefined authorization abilities are denied from F01; the full permission catalogue remains F17.
- Route smoke tests prove each portal prefix/domain grouping can be loaded without sharing protected endpoints.
- Default test environment uses no production services or personal data.
- Pint and complete baseline Pest suite pass.

**PR F02 — Shared operational-context contracts**

- In the Authorization module, introduce immutable portal, actor-source, actor-reference, untrusted/resolved/authorized scope, scope-requirement, and operational-context values.
- Require all external scope candidates to pass through an explicit `OperationalScopeAuthorizer`; do not register a default implementation or authorization bypass.
- Bind only the empty-by-default `OperationalContextStore` with Laravel's scoped lifetime. Request middleware and production F03/F07/F08 adapters remain later work.
- Keep references opaque so F02 does not commit to UUID, ULID, integer, or another database-key strategy.

Tests:

- Context rejects a missing/mismatched portal actor and mismatched account category.
- Institution/semester/period references cannot be trusted until resolved through an authorizer, and mismatched parent chains fail closed.
- Required scope, immutable values, explicit job/CLI actor source, absent default authorizer, and scoped-lifecycle isolation are verified.

### 4.2 Authentication separation: admin, staff, guardian

#### Requirements

- Three separate authentication experiences, each with its own account type, login routes/page, guard/middleware, password setup/recovery, session boundary, dashboard/layout, policies, rate limits/security monitoring, and audit identity.
- A person may eventually hold multiple account types, but authentication never silently crosses portals.
- Staff records do not automatically create accounts; non-login staff remain valid.
- Guardian accounts belong to guardians, not students.
- Passwords are securely hashed and never transmitted; setup/recovery uses short-lived codes or secure links and generic anti-enumeration responses.
- Accounts can be activated, suspended, locked, and revoked independently of a person/staff record; session revocation and sensitive-action reconfirmation are required foundations.
- Authentication and sensitive account changes are audited.

#### Blocking unresolved decisions

- Separate account tables/models versus one account table with immutable portal type and separate providers. The specification requires separate account types/guards, not necessarily separate physical tables.
- Login identifiers for admin and staff; guardian national ID is only described as a possibility.
- Whether one person may hold multiple accounts of the same portal type and how accounts link to a person.
- Account state catalogue, lockout thresholds/duration, concurrent-session policy, password policy, MFA/PIN requirements, reset/setup token lifetimes, and session cookie/domain strategy.
- Exact guardian verification evidence/legal eligibility and notification delivery provider/channel. Guardian self-service setup must wait for these decisions.
- Exact administrative account provisioning/recovery governance.

#### Proposed entities and relationships

- `AdministrativeAccount`: independent administrative credential and lifecycle; optional person link only if approved.
- `StaffAccount`: credential linked one-to-one or one-to-many (decision required) with `StaffProfile`; existence is optional.
- `GuardianAccount`: credential linked to a guardian-capable person/profile; guardian-student eligibility is deferred.
- `AccountCredential` or portal-owned password fields: implementation topology pending.
- `AccountVerificationChallenge`: hashed short-lived challenge, purpose, attempts, expiry, consumption, and portal/account reference; provider delivery is deferred.
- `AccountSession`/framework session linkage and `AuthenticationEvent`: account, portal, result/type, time, and safe request metadata.

Accounts reference people/profiles but do not contain permanent identity facts. Suspension/revocation changes the account, not the underlying person or employment history.

#### Authorization boundaries

- Admin credentials authenticate only with the admin guard; staff only with staff; guardian only with guardian.
- A valid session in one portal is anonymous in the other two; password brokers and route names are portal-specific.
- Staff authorization additionally requires an eligible active position and requested operational context; login alone grants no institutional data.
- Guardian login grants no student access until an active, verified, portal-eligible relationship exists in a later release.
- Account administration and session revocation require explicit permissions and audit records.

#### Small PRs and tests

**PR F09 — Approved account topology and three guards** (decision gate required)

- Replace/adapt the generic `User` scaffold only after approving the account ADR.
- Configure three providers, guards, password brokers, route namespaces, middleware, and distinct layouts.

Tests:

- Each account type authenticates only through its portal.
- Cross-guard session reuse and cross-portal protected route access are denied.
- Suspended, locked, revoked, and inactive accounts cannot authenticate as defined.
- A staff profile without an account cannot authenticate; account creation is not automatic.
- Passwords are hashed and hidden from serialization/logging.
- CSRF and secure session configuration tests cover all browser portals.

**PR F10 — Login, logout, throttling, and audit**

Tests:

- Successful/failed login and logout are portal-attributed audit/security events.
- Rate limiting is independent and effective for all three login endpoints.
- Login error messages do not disclose account existence/state.
- Logout and administrative revocation invalidate the correct portal sessions only.

**PR F11 — Setup/recovery primitives** (provider/legal decisions required before guardian activation)

Tests:

- Generic responses are identical for existing and non-existing identifiers.
- Tokens/codes are hashed, short-lived, single-use, purpose-bound, attempt-limited, and portal-bound.
- Only independently verified, active, eligible contacts can receive recovery.
- Passwords/codes are never included in audit payloads.

### 4.3 Organizations and institutions

#### Requirements

- GCV is the single top-level organization; all 19 institutions belong directly to it and are not separate legal tenants.
- Institutions are operationally independent and own/edit their operational records.
- Central management has authorized organization-wide reads/consolidated reporting but ordinarily cannot mutate institution-owned operational facts.
- Exceptional central intervention, if later implemented, requires elevated permission, reason, and permanent audit.
- Storage units are independent institutions; university spaces may use the academic engine; medical/school relationships are future scope.

#### Blocking unresolved decisions

- Final institution identity/contact/address/status fields, stable-code format, multilingual naming, and closure/reactivation rules.
- Whether the model should enforce exactly one organization at database level or merely seed/configure one while retaining a future-capable relation.
- Which institution profile fields are centrally owned versus institution-editable.
- Whether the 19 institutions are authoritative seed/reference data and who supplies approved names/codes/translations.
- Whether exceptional central intervention is included in Foundation; recommendation is to exclude it until its workflow is designed.

#### Proposed entities and relationships

- `Organization`: GCV identity, stable code, localized display names, lifecycle metadata.
- `Institution`: belongs to `Organization` and `InstitutionType`; stable internal ID/code; localized name; lifecycle status; optional contact/location associations after field approval.
- Historical institution-type changes, if allowed, should be effective-dated rather than overwrite module meaning; whether type may change is a decision gate.

#### Authorization boundaries

- System/data administrators with explicit permissions manage organization-owned configuration and can read authorized institution metadata.
- Institution staff read their assigned institution; editing institution profile fields requires an institution-scoped capability and field ownership rules.
- No central role implies mutation of institution-owned operational records.
- Cross-institution access is denied by default; organization-wide reads require an explicit central permission.

#### Small PRs and tests

**PR F03 — Organization and institution-type reference model** ✅ Complete

Approved architecture decisions implemented in F03:

- Unsigned auto-incrementing BIGINT internal primary keys.
- Unique stable string codes as machine identifiers (rows, not enums).
- GCV seeded as the only current organization; schema is future-capable (no single-row DB constraint).
- `name_en` required, `name_ar` nullable; GCV Arabic name left null until officially supplied.
- `active`/`inactive` lifecycle via `is_active` boolean; no soft deletion; inactive records remain queryable.
- No database ENUM columns; no partial actor-audit columns; no created_by/updated_by in F03.
- Module-owned migrations in `Modules/Organization/database/migrations/`.
- `Organization` and `InstitutionType` Eloquent models in `Modules/Organization/app/Models/`.
- Application actions: `CreateOrganization`, `ChangeOrganizationName`, `ActivateOrganization`, `DeactivateOrganization`, `CreateInstitutionType`, `ChangeInstitutionTypeName`, `ActivateInstitutionType`, `DeactivateInstitutionType`.
- Stable codes excluded from `$fillable`; all mutations go through actions.
- Idempotent reference seeders using `firstOrCreate` — preserve administrator-edited display names and lifecycle state.
- Approved institution-type codes: `academy`, `university_space`, `medical_point`, `womens_center`, `storage_unit`.
- No HTTP routes, controllers, Livewire components, or management UI.
- No institutions, module-activation tables, import tables, civil-registry tables, or student tables.
- No default allow-all authorization; future HTTP callers require F17/F19 policy kernel.

Tests added:

- Schema: key strategy, unique codes, required/nullable names, default `is_active`, no soft-delete, no audit columns.
- Organization seeder: idempotency, name preservation, lifecycle preservation, second-org representability.
- InstitutionType seeder: all five codes, idempotency, name/lifecycle preservation, no count enforcement.
- Organization actions: create, change name (code immutability), activate, deactivate (record preserved).
- InstitutionType actions: create, change name (code immutability), activate, deactivate (record preserved).
- Boundary: no Institution/module-activation/import/student tables; no controllers/Livewire/views in module.

**PR F04 — Institution registry**

Tests:

- Central authorized actor can create/read institution metadata; unauthorized actors cannot.
- Institution A staff cannot read or mutate Institution B data.
- Central read-only permission cannot mutate institution-owned fields.
- Lifecycle changes are authorized and audited; historical references remain valid after deactivation.

### 4.4 Institution types and module activation

#### Requirements

- Every institution has a type, and the type controls available modules.
- Supported initial types represent schools/Academies of Hope, university spaces, medical points, women's centers, and storage units.
- University spaces can activate the shared academic engine; medical points initially receive institution/staff functions only.
- Institution types may apply restrained accents/terminology but must remain one GCV product.

#### Blocking unresolved decisions

- Exact module catalogue and stable machine keys.
- Whether type modules are mandatory defaults, allowed capabilities, or automatically active modules.
- Whether individual institutions can override type defaults, who approves overrides, and whether activation is effective-dated.
- Whether module deactivation is allowed when dependent historical data exists and what read-only behavior follows.
- Whether institution type itself may change and how that affects historical module availability.

#### Proposed entities and relationships

- `InstitutionType`: centrally controlled classification with localized label and lifecycle.
- `ModuleDefinition`: code-owned or centrally managed catalogue of bounded capabilities (decision required).
- `InstitutionTypeModule`: type-to-module rule, preferably distinguishing required/default/allowed semantics if approved.
- `InstitutionModuleActivation`: optional institution-specific activation with status, effective interval, actor, and reason; create only if overrides/effective dating are approved.

Module activation is capability configuration, not authorization: both an active module and actor permission/scope are required.

#### Authorization boundaries

- Only explicitly authorized system administrators configure module definitions/type rules.
- Data administrators may view configuration; mutation authority requires a decided permission catalogue.
- Staff cannot activate modules and cannot access routes/actions for inactive modules even if assigned a role permission.
- Deactivated modules retain authorized historical reads; destructive cascading is forbidden.

#### Small PRs and tests

**PR F05 — Feature-module catalogue and institution-type rules** ✅ Complete

Approved architecture decisions implemented in F05:

- Terminology: GCV business capabilities are named `FeatureModule` / `feature_modules` (not `ModuleDefinition`) to avoid collision with Nwidart physical-module concepts.
- All F05 code lives in the existing `Modules/Organization` physical module. No new physical module was created.
- `feature_modules` table: BIGINT PK, unique stable code, `name_en`, nullable `name_ar`, `is_active` boolean (default true), timestamps. No soft-delete, no ENUM, no actor-audit columns.
- `institution_type_feature_rules` table: BIGINT PK, FK to `institution_types` (RESTRICT), FK to `feature_modules` (RESTRICT), bounded `rule` string column (not ENUM), unique on `(institution_type_id, feature_module_id)`, timestamps.
- `FeatureModuleRule` PHP backed enum (`required`/`default`/`allowed`) persisted as bounded strings. PHP case name for `default` is `DefaultEnabled` (reserved PHP keyword workaround); stored value remains `'default'`.
- Rule semantics: `Required` = enabled, cannot disable; `DefaultEnabled` = enabled, may disable (F06); `Allowed` = disabled, may enable (F06); no rule = unavailable, cannot enable.
- Stable feature codes excluded from `$fillable`; all creation goes through `CreateFeatureModule` action.
- Idempotent seeders: `FeatureModuleReferenceSeeder` (6 definitions), `InstitutionTypeFeatureRuleReferenceSeeder` (15 rules across 5 types). Seeder preserves administrator-edited display names; does not silently overwrite changed rule values.
- `InstitutionTypeRuleInterpreter` service answers type-level baseline questions only; no institution-specific overrides (F06); no authorization claims.
- Actions: `CreateFeatureModule`, `ChangeFeatureModuleName`, `ActivateFeatureModule`, `DeactivateFeatureModule`, `AssignInstitutionTypeRule`, `RemoveInstitutionTypeRule`.
- Inactive feature module definitions remain queryable; deactivation does not delete existing type rules.
- No `InstitutionModuleActivation` table, no F06 resolver, no routes, no controllers, no Livewire, no authentication.
- `InstitutionType` gained a `featureRules()` HasMany relationship to `InstitutionTypeFeatureRule`.

Approved institution-type mapping matrix (5 types × 3 rules each = 15 total):

| Feature | academy | university_space | medical_point | womens_center | storage_unit |
|---|:---:|:---:|:---:|:---:|:---:|
| staff_management | required | required | required | required | required |
| academic_management | required | required | — | — | — |
| asset_management | default | default | default | default | default |
| medical_services | — | — | allowed | — | — |
| womens_center_programs | — | — | — | required | — |
| inventory_management | — | — | — | — | required |

Tests added:

- Schema: F05 migrations apply after F03/F04; unique codes; required/nullable names; `is_active` default; no ENUM/soft-delete/audit columns; composite unique constraint; FK columns.
- FeatureModule seeder: all 6 codes; approved labels; active by default; idempotency; name/lifecycle preservation; future-module representability.
- InstitutionTypeFeatureRule seeder: all 15 rules; per-type rule correctness; idempotency; no silent overwrite of changed rules; stable-code-only matching; future-type representability.
- FeatureModule actions: create, change name (code immutability), activate, deactivate (rules preserved), inactive remain queryable.
- Rule actions: assign required/default/allowed; replace existing; reject inactive feature; reject duplicate at DB level; remove; remove no-op; inactive rules remain inspectable.
- Interpreter: required/default/allowed/no-rule semantics for all four questions; no F06 overrides; no authorization implication.
- Boundary: no InstitutionModuleActivation/F06 table; no auth/student/import tables; no new App/Models; no new physical module; no routes/controllers.

**PR F06 — Institution-specific feature overrides and effective resolution** ✅ Complete

Approved architecture decisions implemented in F06:

- Table `institution_feature_overrides`: BIGINT PK, FK→`institutions` (RESTRICT), FK→`feature_modules` (RESTRICT), boolean `is_enabled` (not nullable), nullable `reason` string, unique `(institution_id, feature_module_id)`. No soft-delete, no actor-audit columns, no DB ENUM.
- Only meaningful override rows are stored: `DefaultEnabled` rule → `is_enabled=false` only; `Allowed` rule → `is_enabled=true` only. All other combinations rejected by `SetInstitutionFeatureOverride`.
- RESTRICT FK ensures deactivating or deleting an institution/feature does not cascade-delete historical configuration.
- `reason` is temporarily nullable for F06. Management UI must not expose override mutation until actor tracking, permission checks, and Audit integration exist (post-F17).
- `ResolutionSource` PHP backed string enum: `required`, `type_default`, `institution_override`, `allowed_but_disabled`, `unavailable`, `feature_inactive`, `institution_inactive`.
- `FeatureResolutionResult` — `final readonly` value object exposing: institution, feature, source, `isEnabled()`, `isAvailable()`, `canBeEnabled()`, `canBeDisabled()`, `hasOverride()`, `reasonKey()`.
- Actions: `SetInstitutionFeatureOverride` (transactional upsert, validates all rejection cases), `ClearInstitutionFeatureOverride` (idempotent/no-op per module convention).
- `InstitutionFeatureResolver` service — public methods: `resolve(Institution, FeatureModule)`, `resolveByCode(Institution, string)`, `enabledFor(Institution)` (returns enabled FeatureModule models), `resolveAll(Institution)` (returns all resolution results).
- N+1 prevention: `resolveAll` and `enabledFor` use exactly 3 queries regardless of feature count.
- No global scope added; inactive overrides remain queryable for administration.
- `Institution` gained `featureOverrides()` HasMany; `FeatureModule` gained `institutionOverrides()` HasMany.
- No seeded override rows; existing institutions inherit type-derived baseline. Seeder reruns do not touch override rows.
- No route middleware, no OperationalScopeAuthorizer, no authentication, no routes, no controllers, no Livewire.
- Architecture boundary enforced: Organization test files reference Authorization contracts via string keys only (not `use` imports) to pass the ModuleBoundariesTest scanner.

Resolution table:

| Type rule | Override | Source | isEnabled |
|---|---|---|---|
| required | — | required | true |
| default | none | type_default | true |
| default | is_enabled=false | institution_override | false |
| allowed | none | allowed_but_disabled | false |
| allowed | is_enabled=true | institution_override | true |
| (none) | — | unavailable | false |
| (any) | — | feature_inactive | false |
| (any) | — | institution_inactive | false |

Tests added (67 new tests, 5 files):

- Schema: table existence; FK types; unique constraint; is_enabled non-nullable boolean; reason nullable; no ENUM/soft-delete/actor-audit; RESTRICT FK cascade prevention verified at DB level.
- Override actions: set disable/enable; upsert; all rejection cases (required, redundant, inactive institution, inactive feature, unavailable); clear (existing, no-op, type-rule and feature-module preservation); seeder idempotency across overrides; DB-level uniqueness.
- Resolver: all resolution sources; override + clear cycle for each rule type; inactive feature/institution; inactive type does not erase resolution; display-name/Arabic-name independence; resolveByCode; auth separation structural assertion.
- Listing: enabledFor correctness (required and default enabled; disabled by override excluded; allowed+override included; inactive excluded); resolveAll sources; N+1 bounded at ≤3 queries (asserted); two institutions same type resolve differently; different types use own rules; future feature representability.
- Boundary: no F07/auth/student tables; no App/Models additions; no new physical module; no routes/controllers/Livewire; no authorizer registered; feature enabled ≠ permission; F02 contracts intact.

### 4.5 Academic years, semesters, institution semesters, periods

#### Requirements

- Academic operations are scoped by institution + academic year + semester + operational period, derived through relationships rather than duplicated IDs.
- Administrators define any number of semesters and periods; never assume two.
- Academic year: stable code/name, dates, lifecycle, audit. Semester belongs to year with code/name/order/dates/lifecycle/audit.
- Institution semester activates a semester for an institution, supports independent preparation, lifecycle actions, copy, and actor history; normally one current semester per institution; history remains readable.
- Period is an operating shift within an institution semester, with administrator-defined name/order/times.
- Draft/open/closed/archived behavior and read-only history must be enforced; archiving stays in the same schema.
- Copying creates new draft configuration and never copies operational facts/audits.

#### Blocking unresolved decisions

- Whether non-academic institutions use academic semesters for all, selected, or no operations.
- Whether suggested lifecycle names are final and which transitions/actors are allowed.
- Exact meaning/enforcement of “normally one current semester,” including overlap and future preparation.
- Whether global semesters may overlap; whether institution-semester/period dates may narrow global dates; timezone and overnight-period handling.
- Closure blockers versus warnings and the exact checklist; later-module checks cannot be implemented now.
- Reopening authority/reason/approval, post-open date-change rules, archive prerequisites, and copying selections/idempotency.

#### Proposed entities and relationships

- `AcademicYear` has many `Semester`.
- `Semester` belongs to one `AcademicYear` and has ordered dates/status.
- `InstitutionSemester` belongs to one `Institution` and one `Semester`; stores institution lifecycle/currentness and transition metadata/history, not duplicated academic-year facts.
- `OperationalPeriod` belongs to one `InstitutionSemester`; ordered localized name/code and start/end time.
- `InstitutionSemesterTransition`: immutable transition event with from/to state, actor, reason, timestamp; may be represented through the audit engine if it preserves domain semantics.
- `SemesterCopyOperation`: optional orchestration record for idempotency/source/target/selection; introduce only when copy behavior is approved.

#### Authorization boundaries

- Central data administrators manage global years/semesters and activate institution semesters subject to explicit permission.
- Institution staff read only assigned institution semesters; ordinary mutation is limited by active position, period, and lifecycle.
- Closed/archived scopes are read-only under ordinary permissions. Reopen/archive/copy require dedicated capabilities and audit reasons.
- Staff-supplied institution/semester/period IDs must form one valid relationship chain.
- Central ordinary access remains read-only for institution-owned operational content even when it manages lifecycle configuration.

#### Small PRs and tests

**PR F07 — Academic year and semester catalogue**

Tests:

- Any positive approved number of semesters is allowed; no two-semester assumption.
- Year date order and semester-within-year integrity are enforced at service and database levels where portable.
- Codes/order uniqueness follows the approved scope.
- Unauthorized mutations fail; reads follow central permissions.
- Lifecycle transitions are explicit and audited.

**PR F08 — Institution semesters and periods** (lifecycle decision gate required)

Tests:

- Institution semester belongs to the correct institution/global semester.
- Period count is unrestricted; order is unique within its container; start precedes end under approved overnight rules.
- Institution A staff cannot access Institution B semester/period.
- Period-limited staff cannot access an unassigned period.
- Invalid relationship chains and ordinary archived/closed mutations fail.
- Current-semester invariant and transitions follow the approved rules.
- Copy creates distinct draft configuration, is atomic/idempotent as designed, and excludes all operational/audit facts.

### 4.6 Permanent people identity boundaries

#### Requirements

- A real person is distinct from domain profiles, portal accounts, roles, positions, and assignments.
- Where practical, student/staff/guardian/beneficiary profiles may point to one underlying person without conflating domains.
- Permanent identities are not duplicated per semester; internal keys remain stable when national IDs change.
- National IDs are normalized, validated, protected, and not the sole internal/duplicate-detection identifier; civil registry data is advisory, not unquestionable authority.
- A person can have multiple independently verified phone/email contacts with ownership/type/status/history; recovery uses only verified eligible contacts.
- Sensitive data needs field/record visibility beyond portal membership.

#### Blocking unresolved decisions

- The final unified person model across all profile types (explicit specification decision).
- Required identity fields, multilingual name representation, date precision, gender/reference catalogues, and rules for unknown/provisional people.
- National-ID formats, countries/issuer, uniqueness scope, normalization algorithm, correction/merge/split workflow, encryption/search strategy, and who may view full/masked values.
- Contact ownership vocabulary, verification methods, eligibility, reuse across people, and retention/history rules.
- Duplicate detection/identity resolution and civil-registry refresh/integration.
- Exact field-level visibility classification and guardian/counselor/medical rules.

#### Proposed entities and relationships

- `Person`: stable surrogate ID and approved core identity attributes only; no semester/institution placement.
- `PersonIdentifier`: belongs to person; type/issuer/country, normalized lookup representation, protected display value, verification/status/effective history. Exact protection scheme awaits threat/model decisions.
- `ContactPoint`: phone/email value with type, ownership, verification, eligibility, active interval/history; model whether a contact can be shared only after approval.
- Domain profiles such as `StaffProfile`, future `StudentProfile`, and future `GuardianProfile` reference `Person` separately.
- `IdentityChange`/merge/correction events should be immutable workflows or audit events, not overwrite-only updates; design waits for identity-resolution rules.

#### Authorization boundaries

- General institutional access never implies access to full national ID, all contacts, or other sensitive profile fields.
- Person lookup and duplicate review require explicit purpose/permission and institution/central scope.
- Staff may access only approved fields for people reached through authorized institutional relationships.
- Guardian accounts do not gain person/student access merely by matching a national ID.
- Audit payloads must redact/encrypt sensitive identifiers and contact values according to policy.

#### Small PRs and tests

**PR F12 — Person-model ADR and privacy classification**

- Resolve the unified model and field catalogue before migration work; produce threat/privacy notes and normalization test vectors.

Tests/document checks:

- Architecture examples demonstrate one person with multiple domain profiles/accounts without duplication.
- Data-classification matrix defines read/mutate/audit/export treatment for every proposed field.
- Synthetic test vectors cover normalization, correction, missing IDs, and collisions.

**PR F13 — Person and identifier foundation** (decision gate required)

Tests:

- Stable internal identity survives identifier correction.
- Approved normalized uniqueness and validation rules hold under concurrency.
- Unauthorized/cross-institution full-identifier reads and mutations fail; masking is applied.
- Corrections preserve old/new value, reason, actor, and history without leaking secrets.
- Names alone never trigger automatic identity merge.

**PR F14 — Contact points and verification state**

Tests:

- Multiple contacts and independent verification/history work as approved.
- Inactive, unverified, or ineligible contacts cannot support recovery.
- Unauthorized contact reads/changes and cross-person reassignment fail.
- Sensitive values are absent from logs/audit payloads where required.

### 4.7 Staff positions and institutional assignments

#### Requirements

- `StaffProfile` is distinct from person/account; employment records do not create login access.
- A position assigns staff to an institution and organizational role; assignments further restrict period/class/subject/course access.
- A staff member can work at only one institution at a time; transfer closes prior assignment and creates a new one; history is preserved; overlapping active assignments across institutions are rejected.
- Multiple compatible responsibilities within one institution are permitted.
- Positions are semester-aware. Secretaries are assigned to an institution semester and one or more periods; backend enforcement must support one-period restriction even though current secretaries cover all periods.
- Guards/non-login staff still have profile/position history.

#### Blocking unresolved decisions

- Whether “one institution at a time” is determined by calendar effective dates, institution-semester overlap, employment status, or a combination.
- Exact position/organizational role catalogue, compatibility matrix, start/end granularity, transfer workflow, and acting/delegated positions.
- Whether non-academic staff positions always reference institution semesters (depends on unresolved non-academic cycle decision).
- Distinction between employment assignment, semester position, role grant, and future teaching/course assignment; exact cardinalities.
- Who creates/closes/transfers positions and which changes require principal/deputy/central approval.
- HR field catalogue and visibility/retention rules.

#### Proposed entities and relationships

- `StaffProfile` belongs to `Person`; holds approved permanent employment identity only.
- `PositionDefinition` or controlled role/position reference describes an organizational responsibility without conferring permission by itself.
- `StaffInstitutionAssignment`: effective-dated historical association of staff to one institution; transfer closes one and creates another.
- `StaffPosition`: belongs to staff, institution assignment, position definition, and—when required—an institution semester; multiple compatible positions may coexist in the same institution.
- `StaffPositionPeriod`: many-to-many scope between a position and periods in the same institution semester.
- Future class/subject/course assignments are explicitly outside Foundation and must not be simulated by broad role grants.

#### Authorization boundaries

- Staff account access requires a valid account plus active eligible staff profile/position and matching institution/semester/period.
- A role name or position never grants unrelated institution data.
- Central administrators ordinarily read staff institutional records; silent edits require a future exceptional intervention workflow and are excluded.
- Institution leadership/secretary mutations require explicit permission, same-institution scope, lifecycle allowance, and field-level HR rules.
- Transfer and position closure are audited, transactional historical changes; previous rows are not repointed or overwritten.

#### Small PRs and tests

**PR F15 — Staff profiles and institution-assignment history** (employment-boundary decision gate required)

Tests:

- Staff profile can exist without an account.
- Concurrent attempts cannot create overlapping active cross-institution assignments.
- Same-institution compatible responsibilities remain possible at the assignment level.
- Transfer closes the old assignment and creates a new one atomically; history remains readable.
- Institution A cannot mutate/read restricted Institution B staff data; central read-only cannot mutate.
- Every mutation is authorized and audited.

**PR F16 — Semester positions and period scopes**

Tests:

- Position, assignment, institution semester, and period must belong to the same institution/context.
- A secretary assigned to one period is denied access to another in both queries and mutations.
- Multiple approved same-institution responsibilities work; incompatible/overlapping positions fail according to approved matrix.
- Closed/archived semester mutation is denied.
- Non-login guard position history works without an account/role.
- No class/subject/course access is inferred from a teacher/trainer position.

### 4.8 Authorization and audit foundations

#### Requirements

- Enforce least privilege and server-side institution, semester, period, role, assignment, record-state, and eventually class/subject/course/field scopes.
- Avoid scattered hard-coded role checks; account type, role, position, assignment, and permission remain distinct.
- Central management has explicit organization-wide read capability but ordinarily no institution-owned operational mutation.
- Sensitive domains/fields have separate visibility boundaries.
- Audit must answer actor, account/portal, before/after, time, reason, workflow/import/job context, and affected institution/semester; `updated_at` is insufficient.
- Important changes, corrections, authentication/security events, lifecycle actions, and exceptional intervention are auditable; audit access itself is permission-controlled.
- Audit/history is append-only in behavior; domain statuses are not replaced by soft deletion.

#### Blocking unresolved decisions

- Exact permission catalogue, role templates, direct account overrides, deny precedence, delegation, and principal/deputy distinctions.
- Whether to adopt an authorization/audit package or first-party implementation; package selection requires security/schema review.
- Audit retention, immutability/tamper evidence, before/after redaction/encryption, IP/device collection lawfulness, and access/export policy.
- Definition and workflow for exceptional central intervention.
- Field-level classification matrix and policy ownership.

#### Proposed entities and relationships

- `Permission`: stable action key; `Role`: named bundle; `RolePermission`: bundle mapping.
- `PositionRoleGrant` or equivalent associates authorization roles with active staff positions/context, not merely accounts.
- `AdministrativeAccountRole`; guardian access remains relationship-derived later rather than broad staff-style roles.
- `AccountPermissionOverride` only if explicitly approved; otherwise omit.
- `AuditEvent`: immutable actor/account/portal/action/subject, safe before/after change set, reason, request/correlation ID, institution/institution-semester context, timestamp, and source (web/job/CLI/import).
- `AuthorizationDecisionContext`: runtime object, not necessarily persisted, combining portal account, position, scope, permission, and record state.

#### Authorization boundaries

- Default deny. Permissions authorize actions only after portal, account state, position, institution, semester, period, assignment, module activation, field visibility, and record state all pass.
- Query builders/repositories apply scope before retrieving records; policies recheck individual resources and mutations.
- Central-read and local-mutate permissions are separate. No “super admin” implicit bypass in domain code.
- Audit readers need explicit permission and scope; sensitive payload fields are filtered independently.
- Audit creation must be reliable within or causally linked to the business transaction and cannot be disabled by ordinary users.

#### Small PRs and tests

**PR F17 — Permission catalogue ADR and policy kernel**

Tests:

- Default deny for unknown actions/resources.
- Role alone without active account/position/scope is denied.
- Explicit central organization read succeeds while local mutation fails.
- Cross-institution, wrong-semester, and wrong-period access fail at query and policy layers.
- Inactive module and closed/archived record state deny mutation.
- Direct overrides are absent or obey the approved precedence.

**PR F18 — Append-only audit infrastructure** (retention/redaction decision gate required)

Tests:

- Authorized mutations emit actor, portal, action, subject, correlation, reason where required, and relevant scope.
- Before/after values are correct for permitted fields; secrets and protected values are redacted.
- Audit records cannot be updated/deleted through ordinary application paths.
- Audit write failure follows the approved transaction/failure policy.
- Unauthorized and cross-institution audit reads fail; permitted central audit reads are read-only.
- Web, queued job, and CLI actors are distinguishable.

**PR F19 — Foundation policy integration**

Tests:

- Authorization matrix covers every Foundation read and mutation across admin/staff/guardian portals.
- Cross-institution denial, semester/period scoping, account state, module activation, and lifecycle state are regression-tested.
- No protected Foundation route/action lacks middleware and policy coverage.

### 4.9 Arabic/English localization foundation

#### Requirements

- Arabic and English are first-class; Arabic renders RTL and English LTR.
- Interface labels are translated centrally, not hard-coded through templates.
- Institution terminology may differ by institution type.
- Dates, numbers, names, validation/workflow errors, and future exports must render correctly in both languages.
- Arabic requires a suitable companion font with required glyphs/weights/numerals/punctuation and future PDF embedding rights.
- RTL is not merely a direction flip; layouts/components must be tested in both directions.

#### Blocking unresolved decisions

- Default locale and fallback locale; user preference versus account/portal/browser choice.
- Approved Arabic companion font, numeral convention, calendar/date format, timezone display conventions, name ordering, and translation governance.
- Storage strategy for multilingual institution/type/period names (columns, translation records, or structured values).
- Institution-type terminology catalogue and who owns translations.
- Font licensing/redistribution and future PDF embedding.

#### Proposed entities and relationships

- No translation database is required for static interface copy; use Laravel language files with stable keys.
- `LocalePreference` may belong to each portal account if persistence is approved; otherwise use session preference with explicit fallback.
- Domain labels requiring administrator-managed Arabic/English values use a shared localized-value strategy selected by ADR.
- A `TerminologyResolver` can map stable domain concepts to locale/type-specific labels without changing authorization or schema meaning.

#### Authorization boundaries

- Locale switching is available within each portal and must not alter authorization context.
- Editing controlled domain translations/terminology is central configuration with explicit permission and audit.
- Missing translations must not expose raw sensitive values or framework errors.

#### Small PRs and tests

**PR F20 — Locale routing/preferences and translation conventions**

Tests:

- Arabic and English switching persists according to the approved strategy and stays portal-safe.
- `lang`/`dir` are correct (`ar`/RTL and `en`/LTR), including unauthenticated/auth pages.
- Invalid locales fall back safely.
- Authentication/validation errors are translated without account disclosure differences.
- Translation-key completeness/static checks cover shared Foundation UI.

**PR F21 — Bidirectional component and terminology baseline**

Tests:

- Browser/component tests cover navigation, forms, tables, focus order, icon mirroring rules, and long text in both directions.
- Institution-type terminology resolves without changing stable domain keys.
- Dates/numbers/names follow approved conventions in both locales.
- Accessibility semantics remain equivalent in RTL/LTR.

### 4.10 Initial design-token implementation

#### Requirements

- Centralize brand/design tokens and use semantic component tokens instead of scattered literals.
- Brand anchors: teal `#254151`, gold `#EEC219`, off-white `#EAEAE8`, dark gray `#616153`, red `#D55342`, green `#518245`, black `#000000`.
- Teal carries structure; gold is accent only; green/red convey semantic states but never by color alone.
- WCAG AA contrast targets apply; darker derived red/green text tokens and visible focus indicators are required.
- League Spartan is display type; Montserrat is body type; Arabic companion font remains to be approved. Fallbacks and licensing matter.
- Preserve the supplied dark-logo aspect ratio; keep it replaceable and use only at legible sizes/on suitable surfaces.
- Interfaces should be calm, professional, table-friendly, keyboard-friendly, responsive, and bidirectional.

#### Blocking unresolved decisions

- Approved Arabic font and licensing; source/delivery of League Spartan and Montserrat and their redistribution rights.
- Accessibility-tested derived palette, typography scale/weights, spacing, radii, elevations, borders, icons, breakpoints, dark surfaces/theme, and component specifications.
- Approved light logo, compact wordmark, symbol/favicon, vector master, minimum-size/clear-space rules.
- Whether a dark theme is in Foundation; recommendation is not to imply one until approved.

#### Proposed entities and relationships

No database entities. Implement layered CSS/Tailwind tokens:

1. Immutable brand anchors (`--gcv-color-*`).
2. Derived accessible primitives (shade/contrast/focus values).
3. Semantic tokens (`--color-surface-*`, `--color-text-*`, `--color-action-primary-*`, `--color-status-*-*`).
4. Component tokens consumed by shared Blade/Livewire components.

Locale-aware font stacks and logical CSS properties (`margin-inline`, `padding-inline`, inset logical properties) support RTL/LTR.

#### Authorization boundaries

Design tokens do not authorize actions. Hidden/disabled controls are usability signals only; routes/actions remain policy-protected. Permission-denied, inactive-module, and read-only-state components must communicate the backend result without disclosing inaccessible record details.

#### Small PRs and tests

**PR F22 — Brand anchors and semantic token layer**

Tests:

- Static checks reject raw brand color/font literals outside the token definition/approved generated files.
- Automated contrast checks cover text, action, status, and focus token pairs at WCAG AA targets.
- Production CSS build succeeds and token snapshots remain intentional.

**PR F23 — Minimal shared shell/components in both directions**

- Build only foundational shell primitives (not business screens): logo treatment, typography, buttons, fields, alerts, badges, tables, navigation, empty/error/permission-denied states.

Tests:

- Component/accessibility tests verify labels, keyboard focus, non-color status cues, destructive confirmation styling, and disabled/read-only semantics.
- Visual/browser regression set covers Arabic RTL and English LTR at representative mobile/desktop widths.
- Logo has meaningful alt text when informative, empty alt when decorative, correct aspect ratio, and approved-background use.
- No inaccessible gold/red/green normal-text combinations are used.

## 5. Ordered Foundation pull-request sequence

The consolidated order below keeps changes small and respects dependencies. A PR marked **decision gate** must not begin until its listed business choices are approved.

1. **F01** Repository conventions and test harness.
2. **F02** Shared operational-context contracts.
3. **F03** Organization and institution-type reference model.
4. **F04** Institution registry.
5. **F05** Module catalogue and type rules — **decision gate**.
6. **F06** Institution activation resolver — **decision gate; omit if overrides are rejected**.
7. **F07** Academic year and semester catalogue.
8. **F08** Institution semesters and periods — **decision gate**.
9. **F09** Approved account topology and three guards — **decision gate**.
10. **F10** Portal login/logout, throttling, and authentication audit.
11. **F11** Setup/recovery primitives — **decision gate for identifiers, eligibility, and delivery**.
12. **F12** Person-model ADR and privacy classification — documentation/design PR.
13. **F13** Person and identifiers — **decision gate**.
14. **F14** Contact points and verification state — **decision gate**.
15. **F15** Staff profiles and institution-assignment history — **decision gate**.
16. **F16** Semester positions and period scopes — **decision gate**.
17. **F17** Permission catalogue ADR and policy kernel — **decision gate**.
18. **F18** Append-only audit infrastructure — **decision gate**.
19. **F19** Foundation-wide policy integration and authorization matrix.
20. **F20** Locale routing/preferences and translation conventions — **decision gate for locale formats/font strategy**.
21. **F21** Bidirectional component/terminology baseline.
22. **F22** Brand anchors and accessible semantic token layer.
23. **F23** Minimal shared shell/components in Arabic and English.

F17 should be designed before protected domain mutations are exposed. If implementation sequencing makes F03–F16 temporarily precede the complete permission kernel, those PRs must expose only internal application services or use narrowly coded temporary policies that are removed in F19; no broadly accessible management UI should be merged. Audit primitives may be introduced earlier when needed, but F18 is the point at which the cross-module contract becomes stable.

Every implementation PR must additionally:

- State its functional scope, actors, entities, workflow states, constraints, services, policies, routes/UI, audit events, fixtures, migration/import notes, exclusions, and open questions.
- Include mutation-authorization tests, cross-institution denial tests, and semester/period tests whenever those scopes apply.
- Use synthetic fixtures only.
- Run Pint, relevant Pest tests, the full suite where feasible, frontend build/lint checks, and any architecture/accessibility checks introduced earlier.
- Review the final diff for historical overwrites, duplicated scope IDs, central-edit bypasses, broad role-only checks, and accidental later-module work.

## 6. Decisions required before implementation

These should be resolved as focused ADRs/product decisions, not buried in migrations:

1. The canonical specification path, module-directory convention, and preferred Livewire delivery approach are resolved in F01.
2. Database engine/version and CI environment remain unresolved.
3. Account storage topology, portal login identifiers, account-person cardinalities, security states/policies, provisioning, and recovery delivery.
4. Approved organization/institution fields, names/codes/translations, field ownership, lifecycle, type-change policy, and authoritative list of 19 institutions.
5. Module catalogue, type rule semantics, institution overrides, effective dating, and deactivation behavior.
6. Academic lifecycle states/transitions, current-semester invariant, date/overlap/timezone rules, closure/reopen/archive/copy behavior, and non-academic semester use.
7. Unified person model, identity/contact fields, normalization/validation/uniqueness, protection/search, correction/merge, duplicate resolution, and visibility classification.
8. Staff assignment temporal rule, role/position catalogue and compatibility, semester linkage for non-academic staff, transfer/approval authority, and HR visibility.
9. Permission catalogue, role templates, override/delegation model, principal/deputy split, exceptional central intervention, and deny precedence.
10. Audit package/build choice, retention, tamper resistance, redaction/encryption, lawful metadata, transaction behavior, and audit-reader scopes.
11. Default/fallback locale, preference persistence, multilingual-field storage, terminology ownership, numeral/date/name formats, Arabic font, and font licensing.
12. Accessible derived palette and remaining design-system/logo approvals.

Decisions about guardian legal relationships, student visibility, student transfers, assessment/attendance catalogues, medical-school relationships, women’s-center case management, inventory costing, document signatures/verification, notification channels, imports/exports, hosting/DR/retention, and offline synchronization remain unresolved too, but they do not block the narrowly scoped Foundation pieces unless a PR attempts to cross into those later domains.

## 7. Foundation completion criteria

The Foundation Release is complete only when:

- The three portal sessions and credentials are demonstrably isolated.
- Organization, institution, module, academic-time, person, and staff-history models follow approved ADRs without implementing later modules.
- Backend authorization defaults to deny and proves central read-only, cross-institution denial, semester/period restrictions, account/position requirements, module activation, and lifecycle read-only behavior.
- Historical people identifiers, staff assignments/positions, and lifecycle events are preserved rather than overwritten.
- Sensitive identifiers/contact data are protected according to an approved classification and excluded from unsafe audit/log output.
- Important Foundation authentication/configuration/mutation events have immutable, permission-controlled audit history.
- Arabic RTL and English LTR foundations work across all three portal shells.
- Components consume centralized, accessibility-tested semantic tokens based on the GCV brand.
- The full automated suite, formatting, frontend build, architecture tests, and accessibility/contrast checks pass.
- All remaining decisions and exclusions are reported; no marks, attendance, documents, inventory/assets, medical records, women's-center courses, or other later-module tables/workflows have been introduced.
