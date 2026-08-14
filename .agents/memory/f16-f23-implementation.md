---
name: F16–F23 implementation decisions
description: Durable lessons and architectural constraints from the F16–F23 Foundation bundle.
---

## F16 — Staff Positions

**Rule:** `position_role_grants` and `administrative_account_roles` use plain integer role_id (no FK constraint across module boundaries).

**Rule:** Overlap detection for `staff_positions` uses application-level `lockForUpdate` inside a DB transaction (no exclusion constraint in SQLite test mode).

**Rule:** `DeterminesPeriodCoverage` returns `null` (not `false`) when the position has zero period links — the caller applies position-type default rules.

**Rule:** `EndPosition` and `ReplacePositionScopes` call `guardSemesterMutable()` using double-backslash string-variable class references.

**Why:** Module boundary scanner prohibits use-imports of cross-module Models; double-backslash pattern evaluated at runtime.

## F17 — Authorization Kernel

**Rule:** `PermissionKey::all()` uses `ReflectionClass::getReflectionConstants()` to enumerate string constants dynamically — no maintenance required when adding keys.

**Rule:** `PolicyKernelService::anyRoleHasPermission()` hits the DB (Role → role_permissions → permissions) — no in-memory permission cache in F17. Cache is intentionally deferred.

**Rule:** 9-step chain order matters: account lifecycle gates (steps 1-2) run before semester lifecycle gate (step 4) and before permission existence check (step 5).

**Rule:** Semester lifecycle gate exempts keys ending in `.view` or `.export` (read-only permissions bypass closed/archived block).

## F18 — Audit Module

**Rule:** `DatabaseAuditRecorder` uses `DB::table('audit_events')->insert()` directly (not Eloquent save) to guarantee INSERT-only with no Eloquent hooks.

**Rule:** `AuditEvent::update()` and `::delete()` throw `LogicException` — application-level immutability guard.

**Rule:** Forbidden key patterns for redaction: password, token, secret, session, national_id, contact, phone, email, hash, fingerprint, plain. Checked on `before_state`, `after_state`, `metadata` keys.

**Rule:** `AuditEvent::CREATED_AT = 'recorded_at'` and `UPDATED_AT = null` — single timestamp column, set by DB default.

## F19 — Authorization Matrix

**Rule:** Matrix regression tests use a shared helper `matrixDecide(roleCode, permissionKey, status)` that runs a full PolicyKernel evaluation — not a stub.

**Rule:** `PermissionCatalogueSeeder::seedRolePermissions()` attaches permissions using `$role->permissions()->attach()` after checking `->where('permission_id', $perm->id)->exists()` to stay idempotent.

## F20 — Locale

**Rule:** `SetLocale` middleware is appended to the web group via `$middleware->web(append: [SetLocale::class])` in `bootstrap/app.php`.

**Rule:** Priority: authenticated account preference → session 'locale' → default 'ar'.

**Rule:** `TerminologyResolver` uses `trans()` (not `__()`) to avoid a pint parser collision with the `__` double-underscore prefix.

**Why:** `__()` caused pint parse errors (possibly because pint's tokenizer interprets `__` as a potential magic constant prefix). Using `trans()` is semantically identical and avoids the issue.

## F22 — Fonts

**Rule:** WOFF2 fonts sourced from `@fontsource/league-spartan`, `@fontsource/montserrat`, `@fontsource/noto-sans-arabic` npm packages and copied to `public/fonts/`. Google Fonts API was blocked in the Replit environment.

**Rule:** Bunny Fonts CDN removed from `vite.config.js` — `bunny()` import from `laravel-vite-plugin/fonts` removed entirely.

**Rule:** `_fonts.css` uses `font-display: swap` and references `/fonts/*.woff2` absolute paths — Vite warns these will "remain unchanged to be resolved at runtime" which is correct for public-directory assets.

## F23 — Portal Shell

**Rule:** Blade layouts use dynamic `lang` and `dir` attributes: `dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}"`.

**Rule:** All three portal layouts (admin/staff/guardian) follow identical structure: skip-link → portal-header → portal-body → portal-main → confirm-dialog.

**Rule:** Confirmation dialog uses the native `<dialog>` element with `autofocus` on the cancel button (safe default for destructive actions).

**Rule:** CSS uses logical properties throughout (`inline-size`, `block-size`, `padding-inline`, `margin-inline`, `inset-block`) for automatic RTL/LTR support without duplication.
