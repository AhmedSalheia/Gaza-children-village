---
name: Staff attendance QR system
description: Durable design decisions for the staff attendance + QR credential system added to the Attendance module. Includes security/isolation patterns.
---

# Staff Attendance QR System — Durable Lessons

## HMAC token lookup (O(1) scan validation)
Use `hash_hmac('sha256', $plaintextToken, config('app.key'))` as the stored token_hash. This gives a deterministic hash that can be looked up by value in one DB query. bcrypt cannot do this (non-deterministic). Rotating `app.key` intentionally invalidates all credentials.

**Why:** bcrypt lookup requires iterating all active credentials (O(n)); HMAC is O(1).

**How to apply:** Any credential/API-key system where the token must be looked up server-side by hash. Never store plaintext; never use bcrypt for tokens that need server-side lookup.

## Unique index names must be globally unique within a DB
SQLite (and most RDBMS) require unique index names across the entire database, not just per table. Two tables in the same module sharing an index name (e.g. `sach_record_cycle_unique`) will collide when migrations run.

**Why:** The student attendance correction history and staff attendance correction history both used `sach_record_cycle_unique` — the second migration failed.

**How to apply:** Always prefix index names with the table abbreviation, e.g. `staff_ach_record_cycle_unique` vs `sach_record_cycle_unique`.

## Correction-cycle design: only re-verify advances the cycle
For append-only correction history:
- `correction_cycle` on the record = which correction window is open (starts at 0).
- `CorrectVerifiedRecord` inserts a history row for the current cycle, updates status — does NOT increment cycle.
- `VerifyRecord` (re-verification) increments cycle if a history entry exists for the current cycle. First verification leaves cycle at 0.
- Guard: check `history WHERE record_id=X AND correction_cycle=current_cycle` — if found, block correction (one per window).

**Why:** Incrementing on correction (not on re-verify) caused the guard to check the wrong cycle number after the first correction.

**How to apply:** Any "one correction per verification window" pattern. The verify action is the gate; correction is the write.

## `assertDatabaseCount` — third param is connection, not message
```php
// WRONG — third param is the DB connection name, not an assertion message:
$this->assertDatabaseCount('table', 0, 'This is not a message.');

// CORRECT:
$this->assertDatabaseCount('table', 0);
// assertion context goes in a comment or surrounding test name
```

**Why:** Passing a message string as the third argument makes Laravel try to use it as a connection name, producing a confusing "Database connection [...] not configured" error.
