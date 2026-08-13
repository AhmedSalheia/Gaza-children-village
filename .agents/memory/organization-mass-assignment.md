---
name: Organization module mass-assignment strategy
description: Why codes are excluded from $fillable and how to create records that have a stable code column.
---

## Rule
`Organization` and `InstitutionType` both exclude `code` from `$fillable`. All other mutable fields (`name_en`, `name_ar`, `is_active`) are in `$fillable`.

## Why
Stable codes must never be changed through bulk operations or casual mass-assignment calls. Excluding them from `$fillable` prevents accidental overwrites via `update([...])`, `fill([...])`, or similar calls that pass arbitrary user/API input.

## How to apply

**In actions:** Set code via direct property assignment (`$model->code = $data->code`) before `$model->save()`. This is the only place codes are set.

**In factories:** Laravel's `Factory::create()` internally uses `Model::unguarded()`, so factories bypass `$fillable` and can pass `code` in the definition array without issue.

**In seeders:** Do NOT use `firstOrCreate(['code' => ...], [...])`. Laravel's `firstOrCreate` uses `fill()` (mass assignment) to create the record, so the `code` column is stripped and the INSERT fails with a NOT NULL constraint violation. Use this pattern instead:

```php
if (Organization::where('code', 'gcv')->exists()) {
    return;
}
$org = new Organization;
$org->code = 'gcv';
$org->name_en = 'Gaza Children Village';
$org->save();
```
