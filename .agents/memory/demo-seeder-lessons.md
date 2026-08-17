---
name: Demo workflow seeder lessons
description: Durable design decisions for seeding demo data through real workflow services.
---

- **Every demo seeder guards production itself** (`abort_if(app()->isProduction(), ...)`) — never rely on DatabaseSeeder ordering; seeders are directly runnable via `db:seed --class=`. **Why:** architect review flagged reliance on call order as a severe risk.
- **Never bypass domain invariants with direct table updates for workflow-owned state** — e.g. issuance must use an *active* template version; switch locales by `activate()`-ing through the service (which archives the old version), then create+activate a fresh draft of the old content to restore it. Only truly transient job-owned or UI-less states (draft snapshots, `generating`) may be direct-set, with a comment.
- **Seeders must be runnable without a console command** — the completeness test instantiates DatabaseSeeder directly, so always use `$this->command?->`.
- **Real security machinery pushes back on bulk seeding**: e-signatures need an authenticated staff-guard session (`loginUsingId`/`logout`), and reconfirmation rate limits (5/15min) must be aged out between signings in demo runs.
- **Idempotency markers must be states only the seeder creates** — domain services fire side effects (notifications) during other seeders, so "any row exists" checks false-positive.
- **How to apply:** any future Demo*Seeder for a workflow module; completion review checks all enum states are represented and claims match seeded coverage.
