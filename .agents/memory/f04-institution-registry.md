---
name: F04 Institution registry
description: Decisions and patterns from building the Institution model, actions, factory, seeder, and tests.
---

# F04 Institution registry

## Key decisions

**Code excluded from $fillable (same pattern as Org/InstitutionType)**
Institution.php excludes `code` from `$fillable`. Seeders and CreateInstitution action assign `$inst->code = ...` directly before `save()`.

**Seeder uses provisional codes**
InstitutionReferenceSeeder seeds all 19 institutions with codes like `academy_1`…`academy_8`, `university_space_1`, etc., and English names like "Academy of Hope 1" — these are placeholders until official names are approved. Task #8 tracks replacing them.

**Why:** Official institution names/codes are a blocking unresolved decision (FOUNDATION_PLAN.md). Seeder was written to be idempotent and easy to update; codes are stable so renaming requires a data migration once any environment has operational data.

**Seeder dependency order**
InstitutionReferenceSeeder silently skips rows when GCV org or institution types are missing (returns early / continues). Tests call OrganizationReferenceSeeder → InstitutionTypeReferenceSeeder → InstitutionReferenceSeeder in that order.

**OrganizationBoundaryTest updated**
Removed the F03 assertions "no institutions table" and "no Institution model in Organization module" — both now exist in F04. Added InstitutionBoundaryTest.php for F04-specific boundary checks.

**Relationships are one-sided for now**
Institution has `belongsTo(Organization)` and `belongsTo(InstitutionType)`, but the parent models do not yet have `hasMany(Institution)`. Task #9 tracks adding those.

## How to apply
- Any new seeder that creates code-stable rows: use direct property assignment, not firstOrCreate or fill().
- Any new boundary test added mid-plan: check which F0N assertions become stale and remove/replace them.
