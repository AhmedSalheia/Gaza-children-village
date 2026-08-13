# GCV DATA — Replit Development Environment

## Project overview

This is an **existing** Laravel 13 modular monolith for Gaza Children Village (GCV). It must not be regenerated, scaffolded from scratch, or converted to another stack.

Foundation phases F01 and F02 are complete and must not be recreated:

- **F01** — modular structure (nwidart/laravel-modules v13), seven module shells, portal route separation, architecture tests.
- **F02** — Authorization-owned operational-context contracts (immutable, fail-closed, no default authorizer).

Only explicitly requested Foundation phases may be implemented. Framework replacement and Replit Database require explicit approval.

## Authoritative documents

| Document | Purpose |
|---|---|
| `AGENTS.md` | Engineering authority — development rules and verified commands |
| `docs/SYSTEM_SPECIFICATION.md` | Product authority — requirements and business rules |
| `docs/plans/FOUNDATION_PLAN.md` | Sequencing — controls which phases may be implemented |
| `docs/MODULE_CONVENTIONS.md` | Module boundary rules and allowed dependency directions |
| `docs/OPERATIONAL_CONTEXT.md` | Trusted operational-scope contracts and resolver rules |

## Running the project

```bash
# Install dependencies (first time or after pulling)
composer install --no-interaction --prefer-dist
npm ci

# Create the local .env if it does not exist
cp .env.example .env
php artisan key:generate

# Create the SQLite dev database if it does not exist
touch database/database.sqlite
php artisan migrate

# Start the development server (used by the Replit workflow)
php artisan serve --host=0.0.0.0 --port=5000

# Build frontend assets (production build)
npm run build
```

## Testing

```bash
php artisan test
vendor/bin/pint --test   # code-style check only; use vendor/bin/pint to fix
```

## Database

SQLite is used for the Replit development and test environment. Production targets MySQL/MariaDB (not yet configured). Do not add Replit Database, PostgreSQL, or another database service without explicit approval.

## User preferences

- Work in the existing module structure; do not move modules into `app/`.
- Do not regenerate or replace any Foundation phase already in Git history.
- Do not create business entities, migrations, or routes outside an approved Foundation phase PR.
