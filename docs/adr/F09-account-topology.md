# ADR F09 — Authentication Account Topology

**Status:** Resolved  
**Date:** 14 August 2026  
**Supersedes:** Section 4.2 unresolved decisions in `docs/plans/FOUNDATION_PLAN.md`

---

## Context

The system specification requires three separate authentication experiences. Before implementing any account model, a topology decision was required to settle:

- Whether to use one polymorphic users table or separate tables per portal type.
- What the login identifiers for each portal are.
- How account lifecycle (activation, suspension, locking, revocation) is enforced.
- How account models relate to permanent person and staff records.
- How authenticated accounts map to the existing F02 operational-context actor model.

The F09 decision gate is now resolved with the decisions below.

---

## Decisions

### A. Physical account topology

Use **three separate account models and database tables**:

| Model | Table | Portal |
|---|---|---|
| `AdministrativeAccount` | `administrative_accounts` | Admin Portal |
| `StaffAccount` | `staff_accounts` | Staff Portal |
| `GuardianAccount` | `guardian_accounts` | Parent/Student Portal |

**Why separate tables:**
- Each portal type has a distinct authentication identity, lifecycle policy, and future credential/recovery requirements.
- Separate tables make schema evolution, provider configuration, and boundary enforcement explicit rather than relying on a `type` discriminator.
- A polymorphic table would make it easy to accidentally cross provider boundaries in queries and easier to mistakenly use the wrong guard for a given account.
- Separate Eloquent providers enforce at the database-query level that one guard cannot retrieve another portal's accounts.

Do not use a single polymorphic `users` table.

### B. Portal-to-guard-to-provider mapping

| Portal | Guard | Provider | Model |
|---|---|---|---|
| Admin Portal | `admin` | `administrative_accounts` | `AdministrativeAccount` |
| Staff Portal | `staff` | `staff_accounts` | `StaffAccount` |
| Parent/Student Portal | `guardian` | `guardian_accounts` | `GuardianAccount` |

"Parent/Student Portal" is the user-facing label. Authenticated accounts belong to parents or authorized guardians; never to students directly.

### C. Login identifiers

- **Administrative accounts**: normalized unique `username`.
- **Staff accounts**: normalized unique `username`.
- **Guardian accounts**: opaque normalized `login_identifier`.

Identifier uniqueness is **portal-local**, not global across account types. The same normalized string may exist as a username in administrative_accounts and staff_accounts without conflict.

All identifiers are normalized to lowercase on creation and update.

**Guardian national-ID resolution direction:** The intended final guardian experience will accept a national ID as the login identifier. However, F09 must not store a permanent civil-identity fact directly on an account. Future F11/F13 work will resolve submitted national IDs through approved person-identifier records into the guardian account `login_identifier`. No national-ID columns are added in F09.

### D. Account/profile cardinality

- Staff and guardian account existence is optional. A staff or person record does not automatically create an account.
- Eventually each staff or guardian profile may have at most one account of its corresponding portal type.
- A person may hold different portal account types (e.g., both staff and guardian) when legitimately needed.
- `StaffProfile` and `Person` do not exist yet; profile foreign keys are deferred to F13/F15.
- No `person_id`, `staff_profile_id`, `student_id`, or `national_id` columns are added in F09.

### E. Account lifecycle

A single `AccountStatus` enum is shared by all three account models:

| State | Value | Meaning |
|---|---|---|
| Pending | `pending` | Provisioned but not yet activated |
| Active | `active` | Authentication permitted |
| Suspended | `suspended` | Temporarily denied by an administrator |
| Locked | `locked` | Denied because of a security decision |
| Revoked | `revoked` | Permanently disabled unless an explicitly approved future recovery rule applies |

**Only `active` accounts can authenticate or retain an authenticated session.**

If an account transitions away from `active` after login, the next protected request will fail because the lifecycle-aware provider returns `null` from `retrieveById` for non-active accounts.

Lifecycle checks are enforced centrally in `AccountEloquentUserProvider`, not independently in controllers or middleware.

Automatic lockout thresholds are **not** implemented in F09.

### F. Guard configuration

Three web session guards are configured:

- `admin` (driver: session, provider: administrative_accounts)
- `staff` (driver: session, provider: staff_accounts)
- `guardian` (driver: session, provider: guardian_accounts)

Three password brokers are configured (infrastructure only; recovery endpoints are deferred to F11):

- `admin`
- `staff`
- `guardian`

### G. Default Laravel User scaffold disposition

The default `App\Models\User` model and `users` table scaffold remain for migration compatibility. They are **deprecated** and removed from authentication authority:

- The `users` provider is retained in config but is not assigned to any portal guard.
- No protected portal route uses the `web` guard.
- No `App\Models\User` can authenticate through the `admin`, `staff`, or `guardian` guards.
- The `users` table is not dropped to avoid destructive migration changes; it is annotated as deprecated in its migration.

### H. F02 actor reference mapping

When an authenticated account is mapped to the F02 `ActorReference` value object:

| Account model | Portal | ActorCategory |
|---|---|---|
| `AdministrativeAccount` | `Portal::Admin` | `ActorCategory::AdminAccount` |
| `StaffAccount` | `Portal::Staff` | `ActorCategory::StaffAccount` |
| `GuardianAccount` | `Portal::Guardian` | `ActorCategory::GuardianAccount` |

The opaque `reference` field in `ActorReference` is the account's primary key cast to string.

`AccountActorMapper` (in `Modules\Accounts\Actions`) performs this mapping. It depends only on the public `Authorization` module data surfaces, which is permitted by the module dependency graph.

### I. Authentication versus authorization distinction

Authenticating through a portal guard establishes identity only. It does not grant any data access.

- **Staff** authentication grants no institutional data access. Staff operational access additionally requires eligible active positions, an F02 trusted operational context, and Authorization policies (future work).
- **Guardian** authentication grants no student access until a future verified guardian-student relationship exists (future F13/F15 work).

---

## Deferred decisions

The following decisions are explicitly **not** made in F09:

- Automatic lockout thresholds
- Login rate limits
- Concurrent-session policy
- MFA or PIN authentication
- Password setup/reset delivery and verification (F11)
- Guardian verification evidence and guardian-student eligibility (F13/F15)
- Administrative provisioning UI
- Authentication audit events (F10/F18)
- Session-revocation workflows
- Staff profile and person model linkage (F13/F15)
- Roles and permissions catalogue (F17)

---

## Consequences

- Three account tables with clean schema boundaries prevent cross-portal data leakage at the query level.
- The lifecycle-aware provider enforces fail-closed authentication without adding logic to controllers.
- The F02 actor model remains authoritative for operational context; Accounts only maps an account to an ActorReference and does not replicate F02 data objects.
- Future profile linkage (F13/F15) will add nullable foreign keys to the account tables without changing the authentication topology.
- The `users` table and generic User model remain but are clearly documented as deprecated scaffolding.
