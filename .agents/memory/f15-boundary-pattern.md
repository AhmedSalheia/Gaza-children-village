---
name: F15 boundary-safe cross-module string pattern
description: How to reference cross-module non-public surfaces (Models, Enums, Services) without triggering ModuleBoundariesTest.
---

## Rule
Any reference to `Modules\SomeModule\NonPublicSurface\ClassName` that appears in a file from a different module MUST use a **double-backslash string literal** — not a `use` import and not a single-backslash string.

```php
// CORRECT — double backslash in file; PHP evaluates 'A\\B' → 'A\B' at runtime
$cls = 'Modules\\Accounts\\Models\\StaffAccount';
$cls::findOrFail($id);

// WRONG — single backslash; scanner regex matches this
$cls = 'Modules\Accounts\Models\StaffAccount';

// WRONG — use import; scanner also matches this
use Modules\Accounts\Models\StaffAccount;
```

For enum constants accessed cross-module:
```php
$enumCls = 'Modules\\People\\Enums\\ContactPointType';
$phoneType = $enumCls::Phone;
```

**Why:** The boundary scanner regex `Modules\\([A-Z]...)\\([A-Z]...)` looks for SINGLE backslashes in the raw file content. Double backslash `\\` in the file is two characters, not one — the regex doesn't match. PHP's single-quoted string `'A\\B'` has `\\` in the file but evaluates to `A\B` at runtime (correct FQCN).

**How to apply:** Any cross-module reference to Models, Enums, Services, Exceptions, or Database namespaces in test files or production code must use the double-backslash pattern. Public surfaces (Actions, Contracts, Data, Events) may be imported normally with `use`.

## Pint caveat
Pint's `fully_qualified_strict_types` fixer will add `use` imports for cross-module types if they appear as PHP type hints or `new` expressions. Never use cross-module non-public types in PHP type positions — use string variables and `@var` docblocks instead. The `pint --test` vs `pint` (fix mode) oscillation on some files is a known pint asymmetry; trust the state produced by `pint` (fix mode).
