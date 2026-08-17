---
name: Reporting module
description: Durable lessons from building the Reporting module (report centre, exports, scoped queries).
---

# Reporting module lessons

- **Never trust hydrated Livewire flags for privileged actions.** `canExport`-style public properties are client-forgeable; re-run `adminCan()`/`staffCan()` inside every export/mutation action, not just `mount()`.
  **Why:** architect review flagged a forged-property export bypass.
  **How to apply:** any Livewire action that gates on a permission must re-check server-side inside the action.
- **Staff scope must be rebuilt from the trusted position on every query** — semester + institution ids come from `staffScope()`, never client input; period-restricted positions get `allowedPeriodIds`, zero grants → zero rows. Staff-attendance rows restrict on `staff_attendance_records.operational_period_id`; class-group-bound families restrict on `class_groups.operational_period_id`.
- **Bounded sync/async export probe:** decide sync vs queued export by fetching `threshold+1` rows, never by materializing the full result set.
- **Sanitize every sheet** of an Excel export (Meta sheets carry user-supplied filter values), not just the data sheet.
- **Re-authorize at every time-separated trust boundary**, not just the initiating action: queued export jobs must re-resolve current permissions AND rebuild staff scope from the live position at execution time, and download endpoints must re-check permissions before serving a saved file — otherwise revoked users retain access via saved URLs or pending jobs.
- **A null staff operational scope means DENY, not "no filter".** `staff_positions.institution_semester_id` is nullable; casting a null semester/institution to 0 (or skipping the where) silently turns a scoped query into a global one. Refuse to build a report scope when either trusted id is missing.
- `tests/Architecture/ModuleBoundariesTest` asserts an exact `dependencies` count in `config/module-boundaries.php` — bump it when registering a new module. Several other boundary guard tests (module count 7, Person::factory, Attachments\Models) were already failing at baseline before Reporting.
- Full pest suite needs `php -d memory_limit=512M` (mpdf blows the 128M default).
