---
name: F07 cross-module boundary pattern
description: How AcademicCalendar references Organization without triggering the boundary scanner — string-literal approach, applies to all future modules.
---

## The problem

The boundary scanner (`ModuleBoundariesTest`) reads raw PHP file bytes and matches `Modules\X\Y` (single backslash) using a lookbehind regex. It flags any reference where `Y` is not in the approved public surface list (`Actions`, `Contracts`, `Data`, `Events`). So `Models` and `Database` are non-public surfaces, and direct `use Modules\Organization\Models\Organization;` imports are rejected — even in test files.

Pint's `fully_qualified_strict_types` fixer also converts leading-backslash FQCNs (`\Modules\Org\Models\Org`) into `use` imports, so the leading-backslash workaround used in F06 doesn't survive pint in PHP files.

## Approved solution: string-variable static calls

Store the FQCN in a local string variable, then call `::new()` or use it in `belongsTo()`:

```php
// Factory / test — calling static factory method:
$orgFactory = 'Modules\\Organization\\Database\\Factories\\OrganizationFactory';
$org = $orgFactory::new()->create();

// Model — Eloquent relationship:
public function organization(): BelongsTo
{
    return $this->belongsTo('Modules\\Organization\\Models\\Organization');
}
```

**Why this works:**
- Double-escaped string literals in the PHP source contain `\\` (two chars). The scanner regex matches single-backslash `\` — it does not match the `\\` sequence.
- Pint does not add `use` imports for string literals.
- PHP allows calling static methods on string variables and resolves string class names in `belongsTo()` at runtime via the autoloader.

**How to apply:**
- Every time a new module needs a cross-module reference to `Models`, `Database\Factories`, or any other non-public surface: use the string-variable pattern, not a `use` import.
- Comments/docblocks must also avoid single-backslash `Modules\X\Y` text — write `Modules\\X\\Y` in comments or omit the class path entirely.
- Boundary test assertions checking for cross-module class names in file content must use the double-escaped form: `->toContain('Modules\\\\Organization\\\\Models\\\\Organization')`.
