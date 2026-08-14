---
name: F10 portal authentication patterns
description: Throttle key design, session-version revocation, append-only event pattern, and test quirks introduced in F10.
---

## Throttle key design

`BuildLoginThrottleKey` derives HMAC-SHA256 keys using `APP_KEY` as the secret. Neither raw identifiers nor raw IPs appear in cache keys. Event fingerprints are the first 16 hex characters of the same hash (short enough to be opaque, long enough for internal correlation). Two independent limiters per attempt: per-portal+identifier (default 5/min) and per-portal+IP (default 30/min). All thresholds are env-overridable via `config/portal-auth.php`.

**Why:** Prevents cache-timing attacks that would reveal whether a given identifier exists, and avoids storing PII in the cache layer.

## Session-version revocation mechanism

Each account table has `auth_version` (unsigned int, default 0). `RevokePortalAccountSessions` increments it in a DB transaction. `VerifyPortalSessionVersion` middleware compares `auth_version_{guard}` in the session against the account's live DB value on every protected request. Mismatch → logout only that guard, rotate CSRF token, redirect to portal login. Other guards are untouched.

Middleware alias `portal.version` registered in `bootstrap/app.php`. Stack: `['auth:admin', 'portal.version:admin']`.

**Why:** Allows immediate server-side session invalidation without a session DB table — the version counter is the token. Guard isolation means administrative revocation of one account type cannot affect unrelated portal sessions.

## Guard cache reset in tests

In Laravel tests, the application persists across HTTP requests. The `SessionGuard::$user` cache therefore survives between `$this->post()` / `$this->get()` calls. After calling `RevokePortalAccountSessions` (which increments `auth_version` in the DB), call `app('auth')->forgetGuards()` before the next test request so the guard resolves a fresh user from DB — matching what production does (each PHP request gets a fresh guard).

**How to apply:** Any test that revokes a session and then makes a protected request must call `app('auth')->forgetGuards()` between the revocation and the request.

## Append-only AuthenticationEvent model

`AuthenticationEvent` sets `UPDATED_AT = null` and has only a `created_at` column. `RecordAuthenticationEvent` wraps the insert in a try/catch that silently discards all exceptions — recording failures must never alter the auth outcome.

Raw identifiers, raw IPs, and passwords are never written to event rows. Fingerprints use first-16-hex of HMAC(value).

## PortalAuthConfig factory pattern

`PortalAuthConfig::admin()`, `::staff()`, `::guardian()` are the only construction paths. Guard names and redirect targets never come from request input — they are hardcoded inside these factory methods.

## Unauthenticated redirect routing

`app/Http/Middleware/Authenticate.php` redirects to the portal-specific login route by URL prefix (`admin/` → `admin.login`, etc.). Tests that previously asserted HTTP 401 on unauthenticated requests were updated to `assertRedirect(route('*.login'))` when F10 merged.

## F09 boundary test cleanup on F10 merge

F09BoundaryTest had forward-guard assertions ("no login routes exist in F09"). These were removed when F10 was implemented. The `describe` block was replaced with a comment noting that F10BoundaryTest owns those assertions.
