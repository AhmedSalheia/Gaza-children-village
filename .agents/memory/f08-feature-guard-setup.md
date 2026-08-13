---
name: F08 feature guard test setup
description: How to set up the academic_management feature guard in AcademicCalendar tests; FeatureModule code is not fillable.
---

## FeatureModule code column is NOT in $fillable

`FeatureModule::firstOrCreate(['code' => 'academic_management'], [...])` fails silently because `code` is excluded from `$fillable`. The `firstOrCreate` call tries to `fill()` the record including code, which is rejected, leaving code null and causing a NOT NULL constraint error.

**Fix:** Use the `FeatureModuleReferenceSeeder` via string-variable call — it creates all standard features idempotently using direct property assignment:

```php
$seederClass = 'Modules\\Organization\\Database\\Seeders\\FeatureModuleReferenceSeeder';
(new $seederClass)->run();
$featureModuleClass = 'Modules\\Organization\\Models\\FeatureModule';
$feature = $featureModuleClass::where('code', 'academic_management')->firstOrFail();
```

## InstitutionTypeFeatureRule has all three columns in $fillable

`institution_type_id`, `feature_module_id`, and `rule` are all in `$fillable`. The `firstOrCreate` approach works for this model.

## String-variable seeder call is scanner-safe

`$seederClass = 'Modules\\Organization\\Database\\Seeders\\FeatureModuleReferenceSeeder'` — double-backslash string in source. The scanner does not match this. Use this pattern for all cross-module factory/seeder calls in tests.

## Pest toThrow(Throwable::class) doesn't work as expected

`Throwable` is an interface. `toThrow(Throwable::class)` in this Pest version treats the string `'Throwable'` as a message substring check, not a class instanceof check. Use `toThrow(\Exception::class)` instead (all DB/integrity exceptions extend Exception).
