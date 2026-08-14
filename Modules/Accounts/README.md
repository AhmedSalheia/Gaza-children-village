# Accounts Module

Owns the three portal account models, lifecycle rules, authentication providers, and account contracts for GCV DATA.

All account-domain rules live here. Portal controllers and layouts may consume Accounts contracts but must not own credential or lifecycle logic.

---

## F09 — Approved account topology and three authentication guards

### Account topology decision

Three separate account models and database tables are used. Do not use a single polymorphic users table. See `docs/adr/F09-account-topology.md` for the full ADR.

### Portal mapping

| Model | Table | Guard | Portal |
|---|---|---|---|
| `AdministrativeAccount` | `administrative_accounts` | `admin` | Admin Portal |
| `StaffAccount` | `staff_accounts` | `staff` | Staff Portal |
| `GuardianAccount` | `guardian_accounts` | `guardian` | Parent/Student Portal |

The user-facing name is "Parent/Student Portal"; the authenticated account belongs to a parent or authorized guardian, never to a student directly.

### Login identifiers

- `AdministrativeAccount` and `StaffAccount` — normalized unique `username`
- `GuardianAccount` — opaque normalized `login_identifier`

All identifiers are normalized to lowercase and trimmed on creation and update. Identifier uniqueness is portal-local; the same string may exist as a username in both `administrative_accounts` and `staff_accounts` without conflict.

### Account lifecycle

Shared `AccountStatus` enum:

| State | Value | Can authenticate |
|---|---|---|
| Pending | `pending` | ✗ |
| Active | `active` | ✓ |
| Suspended | `suspended` | ✗ |
| Locked | `locked` | ✗ |
| Revoked | `revoked` | ✗ |

Only `active` accounts may authenticate or retain an authenticated session. The lifecycle-aware `AccountEloquentUserProvider` enforces this at the provider level:

- `validateCredentials` rejects non-active accounts at login time.
- `retrieveById` returns `null` for non-active accounts on every subsequent request, causing the guard to treat an existing session as unauthenticated when the account state changes.

Lifecycle transitions are explicit actions: `ActivateAccount`, `SuspendAccount`, `LockAccount`, `RevokeAccount`. Automatic lockout thresholds are not implemented in F09.

### Password security

- Passwords are always hashed via Laravel's `hashed` cast. Plain-text passwords are never stored.
- Passwords are hidden from `toArray()`, `toJson()`, serialization, and debug output via the `$hidden` array.
- The `remember_token` is similarly hidden.

### Custom provider driver

`AccountEloquentUserProvider` extends Laravel's `EloquentUserProvider` with lifecycle enforcement. It is registered as the `accounts-eloquent` driver by `AccountsServiceProvider`.

All three portal providers use this driver. No provider can retrieve another portal's accounts.

### F02 actor reference mapping

`AccountActorMapper` (in `Actions`) converts an authenticated account model to the F02 `ActorReference` value object:

| Account model | Portal | ActorCategory | Reference |
|---|---|---|---|
| `AdministrativeAccount` | `Portal::Admin` | `ActorCategory::AdminAccount` | Account PK (string) |
| `StaffAccount` | `Portal::Staff` | `ActorCategory::StaffAccount` | Account PK (string) |
| `GuardianAccount` | `Portal::Guardian` | `ActorCategory::GuardianAccount` | Account PK (string) |

### Default Laravel User scaffold disposition

The default `App\Models\User` model and `users` table scaffold are **deprecated**. They are retained for migration compatibility and are not removed. No portal guard references the `users` provider. No protected portal route uses the `web` guard. `App\Models\User` cannot authenticate through the `admin`, `staff`, or `guardian` guards.

### Deferred profile linkage

- `StaffProfile` linkage deferred to F13. A future migration will add a nullable `staff_profile_id` FK to `staff_accounts`.
- Person/guardian linkage deferred to F15. A future migration will add a nullable profile FK to `guardian_accounts`.
- No `person_id`, `national_id`, `staff_profile_id`, or `student_id` columns exist in F09.

Guardian national-ID resolution direction: future F11/F13 work will resolve national IDs through approved person-identifier records into the `login_identifier`. No direct civil-identity columns are added in F09.

### Portal routes and layouts

Each portal has a protected dashboard route and a minimal layout:

- `/admin/dashboard` — requires `auth:admin`; layout: `layouts/admin.blade.php`
- `/staff/dashboard` — requires `auth:staff`; layout: `layouts/staff.blade.php`
- `/guardian/dashboard` — requires `auth:guardian`; layout: `layouts/guardian.blade.php`

No login, logout, recovery, or account-management routes exist in F09. These are deferred to F10/F11.

### Session and CSRF security baseline

- HTTP-only session cookies (default, configured in `config/session.php`)
- SameSite configured for first-party portals
- Secure cookies configurable via `SESSION_SECURE_COOKIE` env; not forced in development
- CSRF middleware enabled for all browser portal route groups
- Session driver is `array` in tests (no database required)
- An authenticated session in one portal is anonymous in the other two guards by design

### Deferred to later phases

- F10: Login, logout, throttling, and audit events
- F11: Password setup and recovery (password broker tables exist but no endpoints)
- F13: StaffProfile model and StaffAccount profile linkage
- F15: Person/guardian profile linkage for GuardianAccount
- F17: Roles and permissions catalogue

---

## File layout

```
Modules/Accounts/
├── app/
│   ├── Actions/
│   │   ├── AccountActorMapper.php          ← F02 actor reference conversion
│   │   ├── ActivateAccount.php             ← Lifecycle: pending → active
│   │   ├── CreateAdministrativeAccount.php
│   │   ├── CreateGuardianAccount.php
│   │   ├── CreateStaffAccount.php
│   │   ├── LockAccount.php                 ← Lifecycle: → locked (security)
│   │   ├── RevokeAccount.php               ← Lifecycle: → revoked (permanent)
│   │   └── SuspendAccount.php              ← Lifecycle: → suspended (temporary)
│   ├── Data/
│   │   ├── CreateAdministrativeAccountData.php
│   │   ├── CreateGuardianAccountData.php
│   │   └── CreateStaffAccountData.php
│   ├── Enums/
│   │   └── AccountStatus.php               ← Shared lifecycle enum (5 states)
│   ├── Models/
│   │   ├── AdministrativeAccount.php       ← Admin Portal credential
│   │   ├── GuardianAccount.php             ← Guardian Portal credential
│   │   └── StaffAccount.php                ← Staff Portal credential
│   ├── Providers/
│   │   ├── AccountEloquentUserProvider.php ← Lifecycle-aware Eloquent provider
│   │   └── AccountsServiceProvider.php     ← Registers accounts-eloquent driver
│   └── Services/
│       └── LoginIdentifierNormalizer.php   ← Normalizes all login identifiers
├── database/
│   ├── factories/
│   │   ├── AdministrativeAccountFactory.php
│   │   ├── GuardianAccountFactory.php
│   │   └── StaffAccountFactory.php
│   └── migrations/
│       ├── 2026_08_14_000001_create_administrative_accounts_table.php
│       ├── 2026_08_14_000002_create_staff_accounts_table.php
│       ├── 2026_08_14_000003_create_guardian_accounts_table.php
│       └── 2026_08_14_000004_create_portal_password_reset_token_tables.php
└── tests/Feature/
    ├── F09AccountModelTest.php      ← Hashing, normalization, casting, lifecycle enum
    ├── F09AccountSchemaTest.php     ← Schema columns, uniqueness, no forbidden columns
    ├── F09AuthenticationTest.php    ← Guard correctness, cross-guard rejection, lifecycle
    ├── F09BoundaryTest.php          ← Module ownership, User isolation, F02 mapping, no F10+
    ├── F09PortalRouteTest.php       ← Route auth enforcement, cross-portal denial
    └── F09SessionSecurityTest.php   ← Session config, portal isolation, guard config
```
