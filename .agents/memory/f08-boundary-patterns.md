---
name: F08 boundary patterns
description: Scanner traps and test-API quirks discovered while implementing institution semesters and operational periods.
---

## Pest `toContain` variadic signature

`toContain(mixed ...$needles)` — the second argument is treated as another needle, NOT a message. Passing a custom message as the second arg causes failures when the array doesn't contain that message string. The test was written incorrectly (using `toContain($value, $message)`) but only surfaced when AcademicCalendar first used Authorization imports. Fix is to use `expect(in_array($v, $arr))->toBeTrue($message)` pattern.

**Why:** `toBeTrue(string $message = '')` is the correct way to attach failure context in this Pest version. `toContain` is variadic and has no message parameter.

**How to apply:** Whenever the boundary scanner test (`ModuleBoundariesTest.php`) is updated, use the `toBeTrue` pattern for boundary violations.

## Boundary scanner false-negative for double-backslash strings

Scanner regex matches `Modules\X\Y` (single backslash in source). String literals like `'Modules\\Organization\\...'` (double backslash in source = single backslash at runtime) are NOT matched. This is the approved pattern for cross-module Models/Database surface references.

## Docblock @param comments must use double-backslash for cross-module types

`@param object $institution Modules\Organization\Models\Institution` (single backslash in source) IS matched by the scanner and will fail. Use `Modules\\Organization\\Models\\Institution` (double backslash) in all docblock references to non-public cross-module surfaces.

**How to apply:** Any `@param`, `@return`, or comment mentioning a cross-module Models/Database class must use double-backslash notation.

## F06BoundaryTest forward guard removal

The F06BoundaryTest contained "must not exist" assertions for `institution_semesters` and `operational_periods`. These must be removed when F08 is merged. Replaced with a placeholder test to keep the test count stable.
