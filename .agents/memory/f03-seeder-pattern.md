---
name: F03 idempotent seeder pattern
description: The approved check-then-create pattern for reference seeders whose key column is not in $fillable.
---

## Rule
Use an explicit existence check followed by direct property assignment, not `firstOrCreate` or `updateOrCreate`, when the lookup key column is excluded from `$fillable`.

## Why
`firstOrCreate` / `updateOrCreate` route through `fill()` for the CREATE path. If the lookup key (e.g. `code`) is not in `$fillable`, it is silently stripped and the INSERT fails with a NOT NULL constraint violation on SQLite (and a similar error on MySQL/MariaDB).

## How to apply

Approved pattern for a single-record reference seeder:
```php
if (Model::where('code', $code)->exists()) {
    return; // preserve admin-edited display names and lifecycle state
}
$m = new Model;
$m->code = $code;
$m->name_en = $nameEn;
$m->is_active = true;
$m->save();
```

Approved pattern for a multi-record reference seeder (loop):
```php
foreach (self::TYPES as $code => $nameEn) {
    if (Model::where('code', $code)->exists()) {
        continue;
    }
    $m = new Model;
    $m->code = $code;
    $m->name_en = $nameEn;
    $m->save();
}
```

This preserves admin-edited values on subsequent runs (idempotent) and correctly inserts all columns regardless of `$fillable`.
