---
name: Publication module tests
description: Patterns for test helpers in result/attendance publication tests — FK chains, fillable exclusions, operational_period_id requirement.
---

# Publication module test helper patterns

## Why
Several fields are excluded from `$fillable` or have non-obvious NOT NULL constraints that cause SQLite FK / NOT NULL violations when using Model constructors in tests. Use raw `DB::table()->insertGetId()` throughout, following the MarksWorkflowTest pattern.

## Rules

### GradingScale.code is NOT fillable
Set `code` directly on the model instance OR use a raw `DB::table('grading_scales')->insertGetId([..., 'code' => '...'])` call — the `CreateGradingScale` action is the cleanest option.

### mark_sheets has intra-module FKs
`teaching_assignment_id`, `class_group_id`, `subject_offering_id` are all constrained FKs within AcademicManagement. You must create real teaching_assignment rows before inserting mark_sheets. Pattern:
```php
$assignId = DB::table('teaching_assignments')->insertGetId([
    'staff_profile_id' => 1,            // cross-module plain int — stub OK
    'institution_semester_id' => $semId,// cross-module plain int — stub OK
    'staff_position_id' => 1,           // cross-module plain int — stub OK
    'class_group_id' => $classGroupId,  // intra-module FK — must be real
    'subject_offering_id' => $offId,    // intra-module FK — must be real
    'starts_on' => today()->subDay()->toDateString(),
    'status' => 'active',
    'created_at' => now(), 'updated_at' => now(),
]);
```

### student_attendance_sheets.operational_period_id is NOT NULL
Always include `'operational_period_id' => 0` (stub int) in attendance sheet inserts.

### StudentEnrollment requires full person → student_profile chain
Must create: `people` → `student_profiles` → `student_enrollments`. Using stub integers for `student_profile_id` fails the FK.

### Pest.php already extends TestCase for Feature/ subdirs
Do NOT use `uses(TestCase::class, RefreshDatabase::class)` in Feature subdirectory tests. Use only `uses(RefreshDatabase::class)`.

## How to apply
When writing any test that touches `mark_sheets`, `teaching_assignments`, `student_attendance_sheets`, or `student_enrollments`, follow the full chain pattern above. Reuse the `resultCtx()` and `attendanceCtx()` helpers in `tests/Feature/Publications/` as reference.
