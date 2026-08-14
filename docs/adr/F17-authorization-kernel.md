# ADR F17 — First-Party Authorization Kernel

**Status:** Accepted  
**Date:** 2026-08-14  
**Modules affected:** Authorization, Accounts, Staff

---

## Context

Foundation phases F03–F16 introduced actors, accounts, positions, and
semester-lifecycle gates, but contained no stable mechanism for asking
"is actor X permitted to do Y in context Z?" Every mutation that needed
a permission check either deferred the question or used a temporary
boolean guard.

GCV DATA is a relatively small, tightly controlled deployment. The
permission surface is bounded (~40 stable keys), the role set is
deliberately limited (12 protected templates), and the policy rules
require custom context beyond what general-purpose RBAC packages expose
(operational scope, position-derived grants, lifecycle-aware
semester/period gates).

Evaluated options:
- **Laravel Gate / Policies** — insufficient for headless API context;
  gate closure registry becomes hard to audit; no first-class period
  or semester scope.
- **Spatie Permission** — widely used, but couples permission storage to
  Eloquent model traits on the authenticatable; Authorization module has
  zero allowed dependencies, so pulling in cross-module traits breaks the
  boundary invariant.
- **First-party kernel** — explicit, auditable, no external runtime
  dependency, designed to carry F16 position scope and F18 audit hooks
  natively.

## Decision

Implement a **first-party authorization kernel** in the Authorization
module, exposed via public-surface contracts in `Modules\Authorization\Contracts`
and `Modules\Authorization\Data`.

### Permission storage (Authorization module)

- `permissions(id, key, description, group, is_system, timestamps)` —
  the stable catalogue; `key` follows `resource.action` dot notation.
- `roles(id, code, label, is_protected, timestamps)` — named role templates;
  `is_protected = true` means code-governed, not user-deletable.
- `role_permissions(id, role_id, permission_id, timestamps)` — many-to-many
  bridge; integer FKs only, no cross-module ORM.

### Grant tables (non-Authorization modules)

Authorization has zero allowed dependencies. Grant rows that link an
account or position to a role live in the module that depends on Authorization:

- `administrative_account_roles(id, administrative_account_id, role_id,
  granted_by, granted_at, revoked_at, timestamps)` — in Accounts module.
- `position_role_grants(id, position_definition, role_id, granted_by,
  timestamps)` — in Staff module; keyed by `position_definition` string
  (not a FK) so the Staff module can seed this without an ORM join into
  Authorization models.

### Policy evaluation: 9-step deny-precedence chain

```
1. Account existence check      — account must exist and not be revoked/locked.
2. Account lifecycle gate       — suspended accounts are denied all actions.
3. Operational scope check      — actor must hold an authorized operational scope
                                  matching the required ScopeRequirement.
4. Semester lifecycle gate      — closed/archived semesters deny ordinary mutations.
5. Permission existence check   — the requested permission key must be registered.
6. Role assignment check        — actor's account must hold at least one role that
                                  has the requested permission.
7. Position-role grant check    — if actor is a staff actor, their current active
                                  positions (via StaffPosition) may yield roles that
                                  carry the permission.
8. Explicit denial list         — reserved for future administrative overrides;
                                  always checked after allow checks.
9. Default deny                 — if no step above returned ALLOW, the answer is DENY.
```

No implicit super-admin bypass. An actor must hold the specific permission
in at least one resolved role.

### Key design constraints

- `PolicyKernel` is a service registered in the container; callers inject it.
- `AuthorizationDecisionContext` is a value object (no mutable state).
- `PolicyDecision` is a sealed result carrying `allowed: bool` and
  `denialReason: ?DenialReason`.
- The kernel is **always** called; results may be cached in-request only
  (never across requests without an explicit cache strategy approved in a
  future ADR).

## Consequences

- Authorization module remains dependency-free; all knowledge of accounts,
  positions, and people stays outside it.
- Adding a permission requires a migration or seeder entry plus a catalogue
  constant; ad-hoc string permission checks in production code are lint-error
  level violations (enforced by architecture test F17).
- Role templates are code-governed; runtime role management by operators is
  limited to assigning/revoking pre-defined role codes to accounts.
- The 9-step chain is extensible: a new step is inserted before "default deny"
  and tested independently.
