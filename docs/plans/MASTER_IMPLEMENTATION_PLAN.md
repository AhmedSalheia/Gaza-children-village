# GCV DATA Master Implementation Plan

**Status:** Foundation ✅ Complete · Student Registry and Enrolment 🚧 In Progress · All other releases 📋 Planned  
**Version:** 1.0  
**Date:** 15 August 2026  
**Source specification:** `docs/SYSTEM_SPECIFICATION.md` v0.2

---

## 1. Foundation Release ✅ COMPLETE

All F01–F23 phases implemented and passing.

**Delivered:**
- Modular Laravel monolith with `nwidart/laravel-modules` 13.x (7 modules: Accounts, Organization, AcademicCalendar, People, Staff, Authorization, Audit)
- Three-portal authentication (admin/staff/guardian guards, login/logout, HMAC throttle, session revocation)
- GCV organization, 19 institutions, institution types, and feature-module activation engine
- Academic years, semesters, institution semesters, and operational periods
- People identities, national-ID normalization, contact points
- Staff profiles, institution assignments, positions, period scopes, and role grants
- Permission catalogue (40+ keys), role catalogue (12 roles), 9-step policy kernel
- Append-only audit module with redaction guards
- Locale routing (Arabic default/RTL, English/LTR), TerminologyResolver
- 4-layer CSS design token system, self-hosted WOFF2 fonts, full portal shell (Blade layouts, confirm dialog, locale switcher, 20+ CSS component classes)

**Test suite:** EXIT:0 (all tests pass) · Pint: clean · npm build: clean

---

## 2. Student Registry and Enrolment Release 🚧 CURRENT

**Goal:** A genuinely usable Admin/Secretary workflow for student registration, guardian relationships, class placement, transfers, and promotion.

**Modules added:** Students, AcademicManagement, CivilRegistry, Imports

### 2.1 Actors

| Actor | Access |
|---|---|
| System administrator | Full read/write including sensitive fields and audit inspection |
| Data administrator | Institution setup, master data, import management, cross-institution read |
| Principal | Institution-scoped student reads, transfer approval, promotion approval |
| Deputy principal | Same as principal with separately audited identity |
| Secretary | Full student/guardian/enrollment CRUD within assigned institution and periods |
| Period-restricted secretary | Secretary scoped to one or more operational periods only |
| Counselor | Welfare/restricted student fields; no enrollment mutation |
| Teacher | Read-only class list for assigned classes; no enrollment mutation |
| Guardian | Portal view of eligible students' basic info and placement; no edits |

### 2.2 Entities

- `StudentProfile` — surrogate PK, stable student_code, person_id, lifecycle_status, registered_on, welfare fields
- `GuardianProfile` — surrogate PK, stable guardian_code, person_id, optional guardian_account_id
- `GuardianStudentRelationship` — relationship type, legal_authority, verification_status, portal_eligible, contact_priority, emergency_contact, starts_on, ends_on, restricted_notes, evidence
- `AcademicLevel` — stable code, name_en/ar, sequence, is_active, institution-type availability
- `Classroom` — institution-owned, stable code, name_en/ar, capacity, is_active
- `ClassGroup` — belongs to institution_semester + operational_period + academic_level + optional classroom; stable code, name_en/ar, capacity, lifecycle_status
- `Subject` — stable code, name_en/ar, is_active
- `InstitutionSubjectOffering` — subject availability in an institution semester (no teacher assignment)
- `StudentEnrollment` — belongs to student_profile + institution_semester + class_group; lifecycle_status, dates
- `PromotionProposal` — source enrollment, proposed outcome, review workflow
- `CivilRegistryRecord` — read-only Gaza civil record; configurable table name
- `ImportBatch`, `ImportFile`, `ImportRow`, `ImportColumnMapping`, `ImportRowResult`, `ImportAppliedRecord`

### 2.3 Workflows

1. **Student registration** — National ID entry → civil-registry lookup → autofill proposal review → Person create/select → StudentProfile create → guardian relationship
2. **Class placement** — Draft enrollment created → class group assigned → enrollment activated when semester opens
3. **Transfer** — Prior enrollment closed → new draft enrollment at target institution → activation
4. **Promotion** — End-of-semester proposals generated → secretary review → principal approval → draft enrollment for next semester
5. **Excel import** — Upload → parse chunks → map columns → validate → preview → approve → apply through domain actions → result report

### 2.4 Permissions (new keys)

`student.view`, `student.view_restricted`, `student.create`, `student.update`, `student.manage`, `guardian_relationship.view`, `guardian_relationship.manage`, `guardian_relationship.verify`, `civil_registry.lookup`, `academic_level.manage`, `classroom.manage`, `class_group.manage`, `subject.manage`, `subject_offering.manage`, `enrollment.view`, `enrollment.manage`, `enrollment.transfer`, `enrollment.promote`, `import.upload`, `import.review`, `import.apply`, `sensitive.export`

### 2.5 Lifecycle States

**StudentProfile:** draft → active → inactive / withdrawn / graduated / deceased  
**StudentEnrollment:** draft → active → completed / promoted / repeating / transferred / withdrawn / suspended / graduated  
**PromotionProposal:** pending → approved / rejected  
**ImportBatch:** uploaded → parsing → ready_for_mapping → validating → ready_for_review → applying → completed / completed_with_errors / failed / cancelled

### 2.6 UI

- Admin: student list/detail/add, guardian relationships, class structure management, civil-registry lookup audit, import management, enrollment/transfer/promotion oversight
- Staff (Secretary): student search/list/add, civil-registry autofill, enrollment/placement, transfers, promotion review, Excel import
- Staff (Principal): transfer/promotion approval
- Staff (Teacher/Counselor): read-only class list and permitted student profile sections
- Guardian: child selector, student identity, current placement (read-only)

### 2.7 Auditing

All student creation, correction, relationship changes, enrollment activation, transfer, promotion approval, and civil-registry lookups are audited via the Audit module. Lookup audits record fingerprint only (no raw national ID). Import audits record batch/actor/institution/changes summary.

### 2.8 Imports / Exports

- Staged Excel/CSV import pipeline for student records
- Downloadable import result reports (CSV, masked sensitive values)
- Class list exports (CSV) for secretaries

### 2.9 Tests

- Student/person identity separation; lifecycle transitions
- Guardian relationship history; portal eligibility derivation
- Cross-institution access denial; period-restricted secretary denial
- Civil registry advisory behavior; no auto-overwrite; no auto-parent creation
- Class relationship validation; conflicting enrollment prevention
- Transfer atomicity; promotion proposal gating
- Import parsing/mapping/preview/apply; skip-existing; conflict; unauthorized rows
- Masked sensitive data; chunked/bounded processing

### 2.10 Dependencies

Foundation complete (F01–F23), specifically: People, Organization, AcademicCalendar, Staff, Authorization, Audit

### 2.11 Decision Gates

- Civil registry table name: configurable at `config/civil-registry.php` (default `gaza_civil_records`)
- No automatic guardian account creation from guardian profiles
- Spreadsheet library: `maatwebsite/excel` (to be confirmed compatible with Laravel 13/PHP 8.4 at install time)
- National IDs as non-PK identifiers (confirmed per spec)

---

## 3. Attendance Release 📋 PLANNED

### 3.1 Actors

Secretary, Lead Teacher, Principal, Deputy Principal, Data Administrator

### 3.2 Entities

- `StudentAttendanceRecord` — enrolled student, class group, date, status (present/absent/late/excused), source (teacher/secretary), verification status
- `StaffAttendanceRecord` — staff profile, institution, date, status, arrival/departure times, absence reason

### 3.3 Workflows

1. **Teacher attendance** — Lead teacher records class attendance → submits for verification → Secretary reviews/corrects/verifies → official record created
2. **Secretary attendance** — Secretary records directly → official (no teacher step required)
3. **Staff attendance** — Secretary or designated staff records for all staff including non-login staff

### 3.4 Permissions

`attendance.student.view`, `attendance.student.enter`, `attendance.student.verify`, `attendance.student.correct`, `attendance.staff.view`, `attendance.staff.enter`, `attendance.staff.correct`

### 3.5 Lifecycle States

Student attendance: draft → submitted → verified / returned  
Corrections: with before/after values, reason, actor, approval

### 3.6 UI

- Teacher: attendance sheet per class, submit form
- Secretary: attendance verification list, correction form, staff attendance entry
- Principal: attendance monitoring dashboard

### 3.7 Auditing

All corrections must record old/new values, reason, and actor. Verified records require authorized correction workflow to change.

### 3.8 Imports / Exports

- Attendance summary exports (Excel, PDF)
- Configurable attendance publication to guardian portal

### 3.9 Tests

- Teacher cannot enter attendance for non-lead class
- Date must fall within semester bounds
- Secretary verification changes source of truth
- Correction after verification requires reason and audit
- Non-login staff appear in attendance records
- Period-scoped secretary cannot access other periods' attendance

### 3.10 Dependencies

Student Registry and Enrolment release (StudentEnrollment, ClassGroup, InstitutionSubjectOffering)

### 3.11 Decision Gates

- Attendance status catalogue (present/absent/late/excused) — currently assumed; final catalogue requires confirmation
- Lateness threshold definition (minutes before counted absent)
- Whether guardian can see daily attendance or only summaries
- QR/scanning input method architecture (deferred)
- Correction approval requirements (secretary self-approved vs. principal required for corrections beyond N days)

---

## 4. Assessments, Marks, Results, and Publication Release 📋 PLANNED

### 4.1 Actors

Teacher, Secretary, Principal, Deputy Principal, Data Administrator, Guardian (read published only)

### 4.2 Entities

- `AssessmentType` — stable code, name_en/ar, weight, sequence (e.g. midterm, final, continuous)
- `Assessment` — belongs to institution_semester + class_group + subject + assessment_type; mark window open/close dates
- `StudentMark` — enrolled student + assessment, raw mark, grade, status (draft/submitted/returned/verified/approved/published)
- `ResultSnapshot` — approved, versioned published result set; immutable once published

### 4.3 Workflows

1. Teacher enters draft marks → submits → Secretary verifies completeness → returns if incomplete → Principal approves → Authorized user publishes → Guardian sees published result
2. Published results are snapshots; corrections to published data require reissue/supersession, never silent overwrite

### 4.4 Permissions

`marks.enter`, `marks.submit`, `marks.verify`, `marks.approve`, `marks.publish`, `marks.correct`, `results.view`, `results.export`

### 4.5 Lifecycle States

StudentMark: draft → submitted → returned → verified → approved → published  
ResultSnapshot: draft → published (immutable)

### 4.6 UI

- Teacher: mark entry form per class/subject, submission status
- Secretary: mark completeness dashboard, verification interface
- Principal: approval interface, publication trigger
- Guardian: published results per semester (read-only)

### 4.7 Auditing

All mark changes record before/after values, actor, reason. Published snapshots record version, approver, timestamp, content hash.

### 4.8 Imports / Exports

- Bulk mark import from teacher Excel submission (staged pipeline)
- Grade-report PDF per student
- Class result summary Excel

### 4.9 Tests

- Teacher cannot enter marks for unassigned class/subject
- Mark entry window enforcement
- Submitted marks not editable without authorized correction workflow
- Published result not altered by later mark corrections (immutable snapshot)
- Guardian sees only published data

### 4.10 Dependencies

Attendance release (for complete semester data before publication)

### 4.11 Decision Gates

- Final assessment types and weight formulas
- Pass/fail threshold rules and promotion calculation
- Whether marks are numeric, letter-grade, or both
- Correction approval level (secretary vs. principal)
- Result calculation service scope (in-system vs. exported to external tool)

---

## 5. Parent / Student Portal Release 📋 PLANNED

### 5.1 Actors

Guardian (authenticated), System Administrator (account management)

### 5.2 Entities

- Guardian portal account (already in Foundation)
- Guardian–student relationship eligibility (from Student Registry)
- Published data views (marks, attendance, placement, documents)

### 5.3 Workflows

1. **Account setup** — Guardian submits national ID → system checks eligible relationship + verified contact → sends short-lived code → guardian creates password
2. **Child selection** — Guardian sees all eligible children → selects one → views published data
3. **Correction request** — Guardian submits correction + evidence → Secretary reviews → approved/rejected → guardian notified
4. **Document request** — Guardian requests official document → Secretary prepares → Principal approves if required → document generated → guardian downloads

### 5.4 Permissions

`guardian_portal.access`, `student.view_published`, `correction_request.submit`, `document_request.submit`

### 5.5 Lifecycle States

Correction request: submitted → under_review → approved / rejected / returned  
Document request: submitted → preparing → approved / rejected → issued

### 5.6 UI

- Guardian login, first-time setup, recovery (anti-enumeration responses)
- Child selector dashboard
- Student profile (published fields only)
- Placement, marks, attendance (published only)
- Correction request form and status tracker
- Document request form and download list

### 5.7 Auditing

Guardian login events, account setup, correction request submissions and decisions, document downloads

### 5.8 Imports / Exports

Document downloads (PDF) for guardians

### 5.9 Tests

- Guardian with no eligible relationship sees empty state
- Expired relationship does not grant access
- Marks visible only when published
- Anti-enumeration: identical response for existing/non-existing national ID
- Correction request creates audit trail

### 5.10 Dependencies

Student Registry and Enrolment (guardian relationships), Assessments/Publication (published data)

### 5.11 Decision Gates

- Exact guardian verification evidence and legal eligibility rules
- Notification provider and delivery channel (SMS vs. in-app)
- Whether guardian can see unpublished attendance or only summaries
- Password/PIN policy and MFA requirements for guardian accounts

---

## 6. Documents, Requests, Reports, and Exports Release 📋 PLANNED

### 6.1 Actors

Secretary, Principal, Deputy Principal, Guardian, Data Administrator, System Administrator

### 6.2 Entities

- `DocumentTemplate` — versioned, Arabic/English, institution-branded PDF/Excel template
- `IssuedDocument` — immutable generated file; unique document number; verification code
- `FormalRequest` — institution-to-GCV request with approval workflow, attachments, status history
- `CorrectionRequest` — guardian or internal flagging of a record requiring review

### 6.3 Workflows

Document generation, approval, versioning, verification, reissuance, cancellation. Formal requests with preparation → principal approval → central management response cycle.

### 6.4 Permissions

`document.generate`, `document.approve`, `document.publish`, `document.verify_public`, `formal_request.submit`, `formal_request.approve`, `formal_request.respond`

### 6.5 Decision Gates

- Legal requirements for electronic vs. cryptographic signatures
- PDF engine selection (PHP-based vs. external service)
- Document numbering scheme (sequential, institution-scoped)
- External verification endpoint design (public URL or QR code)

---

## 7. Student Welfare and Behaviour Boundaries Release 📋 PLANNED

### 7.1 Actors

Counselor, Secretary (limited view), Principal, System Administrator

### 7.2 Entities

- `CounselorNote` — restricted to counselor role; student, date, category, content, follow-up status
- `WelfareFlag` — raised concern with priority and status
- `BehaviourRecord` — incident type, severity, action taken, actor

### 7.3 Decision Gates

- Exact counselor and safeguarding visibility policy
- Whether behaviour records are visible to principals only or also secretaries
- Data retention rules for sensitive notes
- Cross-module safeguarding alerts (not implemented until policy agreed)

---

## 8. Medical Point Release 📋 PLANNED

### 8.1 Actors

Medical Staff, System Administrator

### 8.2 Entities (Future Clinical Scope)

- `BeneficiaryVisit` — date, complaint, treatment, referring doctor
- `HealthCondition` — student/beneficiary, type, date recorded, active
- `Medication` — beneficiary, name, dosage, duration
- `MedicalReferral` — referral target, outcome

### 8.3 Decision Gates

- Medical record legal and data-protection requirements
- Relationship between medical points and served schools
- Visibility policy for medical data to schools (if any)
- Clinical workflow approval and audit requirements

---

## 9. Storage and Inventory Release 📋 PLANNED

### 9.1 Actors

Storage Unit Staff, Data Administrator, Principal (receiving requests)

### 9.2 Entities

- `StockItem` — catalogue entry; category, description, unit, is_asset
- `StockLocation` — within storage unit
- `StockMovement` — receipt, issue, return, transfer; from/to; quantity; actor; reference
- `StockBalance` — derived from movements or reconciled
- `InstitutionStockRequest` — requesting institution, items, approval workflow

### 9.3 Decision Gates

- Costing and finance integration
- Lot/expiry/serial tracking requirements
- Whether stock balances are materialized or always derived
- Approval levels for inter-institution transfers

---

## 10. Assets and Facilities Release 📋 PLANNED

### 10.1 Actors

Secretary, Principal, Data Administrator

### 10.2 Entities

- `Asset` — category, owning institution, serial/asset number, acquisition, condition, status, custodian
- `AssetMovement` — transfer, maintenance, damage, retirement

### 10.3 Decision Gates

- Asset tagging and verification method
- Depreciation and finance reporting requirements
- Whether assets are shared or institution-exclusive

---

## 11. Women's Center Release 📋 PLANNED

### 11.1 Actors

Women's Center Administrator, Trainer, Data Administrator, Beneficiary (future portal)

### 11.2 Entities

- `Beneficiary` — center-specific profile; not shared with school student profiles
- `Programme` — center-level program definition
- `Course` — belongs to programme; schedule, cohort, trainer assignment
- `CourseEnrolment` — beneficiary in course; attendance, outcome, certificate
- `CenterAsset` — center-owned asset management

### 11.3 Decision Gates

- Whether beneficiaries may have national IDs and how they link to the People module
- Verification and case-management rules for centers
- Whether trainer portal is a sub-section of the staff portal or separate

---

## 12. Notifications Release 📋 PLANNED

### 12.1 Actors

All portal users

### 12.2 Entities

- `NotificationEvent` — type, recipient, channel, status, sent_at, payload (no sensitive data)
- `NotificationTemplate` — per-type, bilingual, with variable substitution

### 12.3 Channels

In-app (first), SMS (future), email (future)

### 12.4 Decision Gates

- Notification provider selection (SMS, email)
- Whether notifications may carry any student data or only status changes
- In-app notification persistence and read/unread tracking
- Opt-out requirements

---

## 13. Full Admin Portal Release 📋 PLANNED

### 13.1 Actors

System Administrator, Data Administrator

### 13.2 Scope

Complete account management, roles/permissions management UI, institution configuration, module activation, master data, duplicate detection, audit inspection, cross-institution reports, system health monitoring, queue status, failed job management, backup/restoration controls, document template management, organization-wide dashboards

### 13.3 Decision Gates

- Whether role editing UI allows custom permission combinations or only predefined role templates
- Audit log retention and archiving rules
- Backup destination and restoration authorization procedure

---

## 14. Civil Registry and Imports / Exports Release 📋 PLANNED

(Civil registry integration and staged import pipeline are delivered as part of Student Registry and Enrolment. This release covers generalization and extension.)

### 14.1 Scope

- Civil registry data refresh procedure and schedule
- Generalized import pipeline for staff, academic structures, and historical data
- Bulk export engine for large datasets (background jobs, download status)
- Sensitive-field masking rules in all exports
- Organization-wide consolidated data exports

### 14.2 Decision Gates

- Civil registry refresh cadence and authorization
- Whether historical data migration from previous systems uses the import pipeline
- Export format requirements for Ministry of Education reporting

---

## 15. Deployment, Backup, Disaster Recovery, and Offline Synchronization Release 📋 PLANNED

### 15.1 Scope

- Production deployment architecture (hosting, web server, PHP-FPM, queue workers)
- Database backup schedule, retention, and restore testing
- Disaster recovery plan and RTO/RPO targets
- Offline-first architecture decision (if approved): local SQLite + synchronization protocol, conflict resolution, device revocation
- CI/CD pipeline configuration
- Security hardening review (HTTPS enforcement, CSP, rate limits, audit alerting)

### 15.2 Decision Gates

- Hosting provider and infrastructure (cloud vs. on-premise vs. hybrid)
- Offline synchronization architecture (confirmed unresolved per specification)
- Queue backend (Redis vs. database queue)
- Backup destination and encryption requirements
- CI provider and test matrix (PHP version, database engine/version)

---

## 16. Release Dependencies and Sequencing

```
Foundation (Complete)
    └── Student Registry and Enrolment (Current)
            ├── Attendance
            │       └── Assessments, Marks, Results, Publication
            │               └── Parent/Student Portal (full data)
            ├── Documents, Requests, Reports
            │       └── Parent/Student Portal (documents)
            ├── Student Welfare Boundaries
            ├── Civil Registry Extension / Generalized Imports
            └── Full Admin Portal (parallel after Foundation)

Medical Point (after Foundation + Staff)
Storage and Inventory (after Foundation)
Assets and Facilities (after Foundation)
Women's Center (after Foundation)
Notifications (after Parent/Student Portal)
Deployment / DR (ongoing, formalized before production)
```

---

## 17. Architecture Invariants (All Releases)

1. No table per institution, year, or semester.
2. No permanent person record duplicated per semester.
3. No enrollment, position, mark, attendance, or issued document overwritten — history preserved.
4. All cross-module references use approved public surfaces (Actions, Contracts, Data, Events).
5. Module boundary graph is acyclic; enforced by architecture tests.
6. All protected queries and mutations enforce server-side authorization including institution, semester, period, assignment, module, field, and record-state scopes.
7. Audit module receives all important actions; raw national IDs never appear in audit payloads.
8. Foundation authentication, authorization, audit, localization, and design token layers are not reimplemented in later modules.
9. Sensitive domains (medical, counselor, HR, finance) require explicit field- and record-level visibility boundaries, not portal membership alone.
10. Demo/test seeders use only synthetic data; production guard is mandatory.
