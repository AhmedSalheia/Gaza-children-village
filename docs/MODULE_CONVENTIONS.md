# Module conventions

GCV DATA is one Laravel deployment and one relational database organized as a modular monolith with `nwidart/laravel-modules` 13.x. Package-managed modules live under the repository-root `Modules/` directory and use the `Modules\<Module>\` namespace declared by each module's own `composer.json`.

Laravel Modules 13 uses `wikimedia/composer-merge-plugin` to include `Modules/*/composer.json`. Do not add the obsolete root Composer mapping `"Modules\\": "Modules/"`; Laravel Modules removed that instruction in version 11. After adding or renaming a module, run `composer dump-autoload` and confirm it appears in `php artisan module:list`.

## Ownership

Each module owns its domain application code, routes, migrations, factories, seeders, translations, views when needed, and tests. The initial shells deliberately contain none of those business artifacts. They establish only discoverable module packages and empty owned directories for future bounded PRs.

The root Laravel application stays thin. Root code is reserved for framework bootstrapping and genuinely cross-cutting integration, including portal route registration, default authorization behavior, global exception handling, and build configuration. Livewire is the preferred browser UI approach, but UI components belong to the module or portal that owns their workflow.

## Public boundaries

A module may expose deliberate public APIs only through these top-level namespaces:

- `Actions` for stable application operations.
- `Contracts` for interfaces and ports.
- `Data` for immutable DTOs/value messages.
- `Events` for published domain/application events.

Modules must not call another module's controllers, route providers, middleware, requests, Livewire components, Blade components, views, models, repositories, services, or other internal implementation classes. They must never depend on portal controllers or UI components. Events are preferred where the caller should not know the consumer.

The allowed dependency graph is machine-readable in `config/module-boundaries.php` and enforced by architecture tests. It is acyclic:

```text
Authorization   Audit
      ^          ^
      |          |
 Accounts   Organization   People
      ^          ^           ^
      |          |           |
      +------ AcademicCalendar
                     ^
                     |
                   Staff
```

Arrows indicate an allowed dependency on the target's public surface; omitted edges are denied. Changing the graph requires an architecture decision and matching test update.

## Authorization and data rules

Authorization is default-deny from F01. Undefined abilities are denied, and later protected reads and mutations must add explicit policies plus institution, semester, period, assignment, module, field, and record-state checks as applicable. The complete permission catalogue remains deferred to F17.

Only synthetic identities and operational data may be used in tests, factories, or seeders. No later-release module shell or business table may be added during Foundation work unless its bounded PR is explicitly approved.

The package's `modules_statuses.json` only enables code-module discovery. It is not the future institution-type/module-activation business model described for F05/F06.
