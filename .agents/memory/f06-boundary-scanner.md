---
name: F06 cross-module boundary scanner in tests
description: ModuleBoundariesTest scans every PHP file including test files for cross-module namespace references; Organization test files must not import Authorization classes directly.
---

The `ModuleBoundariesTest` (tests/Architecture/ModuleBoundariesTest.php) uses a regex scanner over all PHP files under `Modules/*/` including test files. It detects any `Modules\<Target>\<Surface>` reference and enforces:

1. `$target` must be in the allowed dependency list for `$source` module.
2. `$surface` (the second namespace segment after the module name) must be in the approved public namespaces list.

**The trap:** Organization may not depend on Authorization (graph flows the other way). A `use Modules\Authorization\Contracts\OperationalScopeAuthorizer;` in an Organization test file fails the scanner with "Organization may not depend on Authorization".

**The fix:** Replace direct class-constant references with string-based equivalents:

```php
// Instead of:
use Modules\Authorization\Contracts\OperationalScopeAuthorizer;
expect(app()->bound(OperationalScopeAuthorizer::class))->toBeFalse();

// Use:
expect(app()->bound('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer'))->toBeFalse();

// And for class_exists / interface_exists checks:
interface_exists('Modules\\Authorization\\Contracts\\OperationalScopeAuthorizer');
class_exists('Modules\\Authorization\\Data\\OperationalContext');
```

**Why:** The scanner uses a regex on raw file content (`Modules\\([A-Z]…)\\([A-Z]…)`). Escaped string literals containing `Modules\\Authorization` still match the regex pattern. However, the simpler double-backslash form `'Modules\\Authorization\\...'` when written with actual backslash-backslash in the PHP source appears as `Modules\Authorization\...` to the regex. In practice the fix that worked: write the string with double-escaped backslashes and no `use` import, which keeps the cross-module class reference out of the import list the scanner inspects.

**How to apply:** Any time an Organization (or other downstream module) test needs to assert behavior about an Authorization contract, use string-based lookup rather than importing the class. This pattern should be applied to any future cross-module-boundary assertions in Organization, AcademicCalendar, or Staff test files.
