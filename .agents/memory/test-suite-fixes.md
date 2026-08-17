---
name: Test suite fixes — durable lessons
description: Non-obvious patterns that came up fixing the 160-failure test suite backlog; apply whenever writing new tests or actions.
---

## SQLite date-cast comparison bug

**Rule:** Never use `->where('date_column', '<=', 'Y-m-d')` on a column whose model has a `date` cast. Laravel/Carbon serializes it as `'Y-m-d H:i:s'` which fails `<=` comparisons in SQLite because `'2026-09-01 00:00:00' > '2026-09-01'` lexicographically.

**Why:** SQLite stores dates as text; `'2026-09-01 00:00:00'` is lexicographically greater than `'2026-09-01'`.

**How to apply:** Use `->whereDate('column', '<=', $dateStr)` everywhere the column has a `date` cast. Affected actions confirmed: `StartAssignment`, `AssignPosition`, `ResolveAssignmentOnDate`, `TransferStaff`, `StaffPosition::scopeEffectiveOn`, `StudentProfile::activeGuardianRelationships`, `StudentProfile::portalEligibleRelationships`, `CreateGuardianStudentRelationship`.

---

## Pest `toContain` is variadic — no message parameter

**Rule:** `expect($str)->toContain($needle, 'message')` treats the second arg as ANOTHER needle to search for. The message string will not be found, making the assertion always fail.

**Why:** Pest 2 `toContain(string ...$needles)` is variadic for strings.

**How to apply:** Use `expect($str, 'failure message')->toContain($needle)` — the message goes to `expect()`, not `toContain()`.

---

## HasFactory on Person model requires explicit newFactory() override

**Rule:** Laravel's `HasFactory` default convention looks for `Database\Factories\{Model}Factory` which resolves to the wrong namespace for module-namespaced models. Always override `newFactory()`.

**Why:** Default convention produces `Database\Factories\Modules\People\Models\PersonFactory` instead of `Modules\People\Database\Factories\PersonFactory`.

**How to apply:** Add to any module model using HasFactory:
```php
protected static function newFactory(): YourFactory
{
    return YourFactory::new();
}
```

---

## Boundary guard maintenance — module/table counts must track reality

**Rule:** Every boundary test that asserts a fixed module count (e.g. `count($names) === 7`) must be updated when new modules are added. Same for forbidden-table lists: tables added by later modules must be removed from earlier modules' forbidden lists.

**Why:** Guards that pass stale counts silently stop catching real violations.

**How to apply:** When merging a new module, grep for `toBe(7)\|count.*names.*===` and `forbidden.*table` patterns in boundary tests and update all occurrences.

---

## Conflict flag save inside a rolled-back DB::transaction is lost

**Rule:** If a service method saves a `conflict_flag = true` then throws inside a `DB::transaction()`, the save is rolled back. The flag will never appear in the DB.

**Why:** DB::transaction rolls back ALL writes if any exception escapes it.

**How to apply:** Perform conflict detection (including the flag save) BEFORE entering the transaction block. See `CorrectionApplicationService::apply()` for the reference pattern.

---

## ends_on = today means "ended" — use strict `>` for active-relationship queries

**Rule:** When a relationship/record is ended with `ends_on = today`, the "is it still active?" query must use `orWhereDate('ends_on', '>', today)` — NOT `>=`. Using `>=` treats today's end-date as still active.

**Why:** `EndRelationship` sets `ends_on = today` to deactivate; `>=` comparison includes today, leaving the relationship appearing active.

**How to apply:** All "is active" scopes on guardian relationships use `orWhereDate('ends_on', '>', now()->toDateString())`.
