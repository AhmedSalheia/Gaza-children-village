---
name: Document requests module
description: Durable decisions and schema pitfalls for the student document request/issuance flow.
---

# Document requests module

## generation_failed is NOT terminal

`TERMINAL_STATUSES` on `StudentDocumentRequest` does NOT include `generation_failed`.
The request stays recoverable so the generation job can be re-dispatched.
**Why:** "leaves recoverable state" — terminal means no further transitions; generation_failed needs cancel and retry paths.

## GenerateDocumentJob concurrency contract

The job locks the request row with `lockForUpdate()` at the top of the transaction BEFORE any status check.
Only `approved` and `generation_failed` statuses are actionable — all others (cancelled, issued, generating, rejected) are silent no-ops.
The transition to `generating` and the `issued_documents` INSERT both happen inside the same transaction.
Error boundary: any exception is caught and a separate transaction marks `generation_failed` (no rollback of the failure mark).
**Why:** Without the lock, concurrent dispatches can both pass the idempotency check and produce duplicate documents.

## issued_documents unique constraint

`issued_documents.request_id` has a unique index (`issued_documents_request_id_unique`).
This is the DB-level backstop for the application-level idempotency check.
For reissue, the old document must be cancelled (soft-deleted via `cancelled_at`) before the new request is created — they are separate requests so this constraint is not violated.

## Staff scope: two-column contract

All staff document views and download routes scope by BOTH `institution_id` AND `institution_semester_id`.
Period-restricted positions (secretary, teacher) further filter by `cg.operational_period_id IN allowedPeriodIds()`.
**Why:** Scoping only by institution lets staff in one semester see another semester's data — violating the established staff scope model.

## institution_semester_id stored on requests

`student_document_requests.institution_semester_id` (nullable, added in migration 000007) is populated from the enrollment's `institution_semester_id` at request creation time.
`EnrollmentSnapshotService` joins `institution_semesters → semesters` for the semester name (not `institution_semesters.name` — that column doesn't exist).

## Verification code lookup

`verification_code` (64 hex, plain) is stored for downloads; `verification_code_hash` (SHA-256 of code) has the index used by GET /verify/{code}.
`RateLimiter::hit` uses positional decay param: `RateLimiter::hit($key, 60)` — named `decay:` not supported in this Laravel version.

## Institution seeding chain in tests

Must seed in order: `organizations` → `institution_types` → `institutions`.
`academic_years` requires `organization_id`. `semesters` requires `academic_year_id`, `code`, `sequence`, `starts_on`, `ends_on`.
`class_groups` requires `code`, `name_ar`, `operational_period_id` (NOT NULL, plain int).
`student_profiles` requires `registered_on` (date, NOT NULL). `student_enrollments` requires `enrolled_on` (date, NOT NULL).

## document_template_versions column names

FK to document_templates: `template_id` (not `document_template_id`).
Audit columns: `creator_account_id`, `approver_account_id` (nullable) — not `created_by_account_id` / `activated_by_account_id`.
