# GCV DATA Development Instructions

## Product source of truth

Before planning or implementing features, read:

- `docs/SYSTEM_SPECIFICATION.md`

Treat confirmed business rules in that document as requirements.
Do not silently decide items listed under unresolved decisions.

## Development approach

- Build the application as a modular Laravel monolith.
- Use `nwidart/laravel-modules` 13.x with package-managed modules under the repository-root `Modules/` directory.
- Follow `docs/MODULE_CONVENTIONS.md` for module ownership, public contracts, and allowed dependency direction.
- Keep the root Laravel application thin; Livewire is the preferred browser UI approach.
- Implement one bounded module or workflow at a time.
- Do not attempt to generate the complete system in one task.
- Preserve historical records instead of overwriting historical facts.
- Keep permanent people records separate from semester-specific records.
- Enforce institution, semester, period, role, and assignment scopes in backend authorization.
- Never rely only on hidden interface elements for access control.
- Central management ordinarily has read-only access to institution-owned operational records.
- Support Arabic RTL and English LTR from the beginning.
- Use centralized design tokens based on the brand section of the specification.
- Never use production personal data in fixtures or automated tests.

## Before implementation

For every substantial module:

1. Identify applicable requirements.
2. Identify unresolved decisions.
3. Present a domain model and implementation plan.
4. Ask before making consequential assumptions.
5. Keep the change bounded and reviewable.

## Verification

For every implementation task:

- Add or update automated tests.
- Test cross-institution access denial.
- Test semester and period scoping where applicable.
- Test authorization for every mutation.
- Run formatting and relevant test commands.
- Review the final diff.
- Report unfinished decisions or exclusions.

## Verified repository commands

Run these commands from the repository root with PHP 8.4.1 or newer (required by the locked development dependencies), Composer 2, and Node.js 20.19 or newer:

```text
composer validate --strict
composer dump-autoload
php artisan package:discover --ansi
php artisan about
php artisan module:list
php artisan route:list
vendor/bin/pint
php artisan test
npm run build
```

After adding or renaming a module, verify that `php artisan module:list` reports it with the intended enabled state.

## Code review rules

Flag any change that:

- Allows cross-institution data access without explicit central permission.
- Allows central administrators to silently edit institution-owned records.
- Stores semester-dependent information directly on permanent profiles.
- Overwrites historical enrolments, positions, approvals, marks, or documents.
- Authorizes teachers by role without checking their class and subject assignments.
- Publishes sensitive or unapproved data to guardians.
