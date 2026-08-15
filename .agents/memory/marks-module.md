---
name: Marks module architectural decisions
description: Durable design rules for the AcademicManagement marks subsystem (grading scales, windows, sheets, corrections)
---

# Marks Module — Durable Architectural Lessons

## Never trust a Livewire public property for sheet lookup in mutations
The `sheetId` on `MarkEntrySheet` is UI-state only (tracks whether a sheet was opened).
All mutation methods (`saveMark`, `submit`, `verify`, `confirmReturn`) resolve the sheet
authoritatively via `resolveSheet()` which queries `WHERE teaching_assignment_id = ? AND
mark_entry_window_id = ? AND institution_semester_id = ?`. Any method that accepts a
client-supplied ID (sheetId, enrollmentId, assessmentDefinitionId) must also validate it
belongs to the authorized scope before passing it to a domain action.

**Why:** Livewire wire-model mutations can forge public property values without triggering
route/middleware guards. Scope-check in the domain layer (SaveDraftMarks) is the last defense,
but it only checks DB scope — not actor ownership. The Livewire layer must resolve authoritatively.

**How to apply:** Every marks mutation that loads a model must derive the model from the
actor's authoritative scope (assignmentId + windowId + semester_id), not from a property
the client can set.

## Period-scope guards are required in all staff marks reads
`HasStaffAuth::isFullScopePosition()` / `allowedPeriodIds()` must be applied in every
query in marks Livewire components, mirroring the attendance module pattern:
- Read queries: add `whereIn('cg.operational_period_id', $allowed)` when not full-scope
- Empty allowed list: return `collect()` for reads, `abort(403)` for mutations
- `loadSheetInScope` must join `class_groups` to resolve `operational_period_id`

**Why:** Period-restricted positions (secretary, teacher) must never see data outside their
granted periods — the established staff authorization contract applies equally to marks.

## Assessment definition applicability has four scopes
When checking whether an `assessment_definition` applies to a given mark sheet (in
`OpenMarkSheet` seeding and `SaveDraftMarks` scope check), all four scopes must be handled:
1. `class_group_id = X AND subject_offering_id = Y` — most specific
2. `class_group_id IS NULL AND subject_offering_id = Y` — subject-only
3. `class_group_id = X AND subject_offering_id IS NULL` — class-only ← easy to forget
4. `class_group_id IS NULL AND subject_offering_id IS NULL` — semester-wide

The mark entry UI must use LEFT JOINs to institution_subject_offerings/subjects so
semester-wide definitions (null subject_offering_id) are not silently excluded.

**How to apply:** Any query filtering assessment_definitions by sheet context needs all four
OR branches. A missing class-only branch means class-level assessments are never seeded
or saveable.

## OpenMarkSheet window scope validation order
When a `markEntryWindowId` is supplied to `OpenMarkSheet`, validate in this order:
1. Window exists (404 if not)
2. Window is `open` or `extended` (MarksException)
3. Window's `institution_semester_id` matches assignment's (MarksException)
4. Window's `class_group_id`, if non-null, matches assignment's class group (MarksException)
5. Window's `subject_offering_id`, if non-null, matches assignment's subject (MarksException)

**Why:** A window scoped to semester B cannot gate a sheet in semester A. A window scoped
to class C cannot gate a sheet for class D. Checking existence before scope prevents
information leakage.

## Four-eyes rule: verifier ≠ approver must be enforced in the domain action
`ApproveMarkSheet` checks `$sheet->verified_by_staff_profile_id !== null && equals $staffProfileId`
and throws `MarksException` if same. This cannot be left to the UI alone because Livewire
components can be called with any staff profile ID.

## SaveDraftMarks enrollment scope check uses class_group_id not institution_semester_id
`student_enrollments` has both `class_group_id` (FK, constrained) and `institution_semester_id`
(plain int, cross-module). Checking `class_group_id = sheet.class_group_id AND enrollment_status = 'active'`
is sufficient and more direct — it implies the correct semester and class.

## Correction rows use a partial unique index, not a full unique index
`student_marks` has `UNIQUE(mark_sheet_id, enrollment_id, assessment_definition_id) WHERE correction_of_id IS NULL`.
This allows multiple correction rows with the same triplet (correction_of_id IS NOT NULL) while
preventing duplicate originals. Uses `DB::statement('CREATE UNIQUE INDEX ... WHERE ...')` —
not `$table->unique([...])` which would create a full unique constraint.
