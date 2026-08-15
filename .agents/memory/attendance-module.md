---
name: Attendance module implementation
description: Key decisions and pitfalls from building the Attendance module (student daily attendance lifecycle).
---

# Attendance Module

## Table names
`AttendanceSheet` → `$table = 'student_attendance_sheets'`; `AttendanceRecord` → `$table = 'student_attendance_records'`.
Without explicit `$table`, Laravel derives wrong names (drops the `student_` prefix).

## Date comparison in SQLite
Eloquent's `date` cast serialises as `Y-m-d H:i:s` in SQLite. Use `->whereDate('column', $str)` not `->where('column', $str)` for date-only comparisons.

## student_profiles schema
Correct columns: `lifecycle_status` (not `registration_status`), `registered_on` (required date). Test helpers must include both.

## Re-verification path (reopened → verified)
`SheetStatus::awaitingReview()` returns true for both `Submitted` AND `Reopened`. `VerifySheet` accepts both states. Without this, reopened sheets are permanently stranded — no path back to verified after corrections.

## Scope guard pattern (security)
Components must call `assertSheetInScope(sheetId)` in `mount()` AND in every public mutation method. Mount-time checks alone can be bypassed via forged Livewire messages. `assertSheetInScope()` is in `HasStaffAuth` trait and checks institution_semester_id + period restriction. Teacher homeroom check (`assertHomeroomIfTeacher`) is attendance-domain-specific and lives in the Livewire component, not the trait.

## UI record entry — Alpine.js per-row pattern
Status selects use `x-data` Alpine per-row state with `$wire.saveRow(enrollmentId, statusCode, reason, arrivedAt, departedAt)` call. Reason/time fields are shown/hidden via `x-show` based on the status catalogue metadata. Never use `wire:change` that fires immediately without reason/time fields — it causes excused_absence to always fail validation.

## Unique constraint
`UNIQUE(class_group_id, attendance_date)` on `student_attendance_sheets` enforces one-sheet-per-class-per-day at DB level. App-layer duplicate check alone is not sufficient against concurrent creates. Migration: `2026_08_17_000003_add_unique_constraint_to_attendance_sheets.php`.
