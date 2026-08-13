# GCV DATA — System Description and Functional Specification

**Document status:** Working product specification  
**Version:** 0.2  
**Date:** 12 August 2026  
**Audience:** GCV stakeholders, software developers, and AI-assisted development tools  

---

## 1. Purpose of this document

This document defines the current agreed scope, architecture, users, permissions, modules, workflows, data boundaries, and delivery order for **GCV DATA**.

It is intended to:

- Give developers a shared understanding of the complete system.
- Serve as the main product description in the GitHub repository.
- Provide reliable context to AI-assisted development tools such as Claude, Replit, Codex, or similar tools.
- Prevent individual modules from being generated with conflicting assumptions.
- Clearly separate confirmed requirements from future ideas and unresolved decisions.

This is a functional and architectural specification, not a final database schema or a collection of implementation tickets. Detailed schemas, API contracts, screen designs, and acceptance tests should be derived from it module by module.

---

## 2. Product summary

GCV DATA is a medium-sized, multi-institution education and operations management platform for **Gaza Children Village (GCV)**.

The system will centrally represent GCV and its 19 institutions while allowing each institution to manage its own operational data. Central management can view organization-wide information, receive requests, manage shared configuration, and produce consolidated reports, but it does not ordinarily edit institution-owned records directly.

GCV DATA will include separate experiences for:

1. System and data administrators.
2. Staff working in GCV institutions.
3. Parents and authorized guardians viewing student information.

The largest initial functional areas are school administration and the parent/student portal. The architecture must also support women’s centers, medical points, storage units, and university spaces without forcing all of them into school-specific concepts.

---

## 3. Organizational scope

GCV is the single top-level organization currently managed by the system.

| Institution type | Count | Main responsibility in the system |
|---|---:|---|
| Schools / Academies of Hope | 8 | Complete school and student administration |
| University spaces | 2 | Academic operations using the shared education engine |
| Medical points | 2 | Institution and staff management initially; clinical functionality later |
| Women’s centers | 2 | Courses, beneficiaries, trainers, attendance, outcomes, and assets |
| Storage units | 5 | Independent stock locations and future inventory operations |
| **Total** | **19** | Centrally visible, institutionally managed |

### 3.1 Organizational rules

- All 19 institutions belong directly to GCV.
- GCV is the organization; institutions are not separate organizations.
- The current product is multi-institution. It should not pretend each institution is a fully isolated legal tenant.
- Every institution has a type that determines which modules are available.
- Institutions own and edit their operational records.
- Central management can view authorized records and consolidated reports across all institutions.
- Central management should request correction of institution-owned records instead of silently rewriting them.
- Exceptional central intervention, if implemented, must require an elevated permission, a reason, and permanent audit history.
- Storage units are independent institutions because stock must be traceable to an exact unit.
- University spaces behave like schools when performing academic operations, although their labels and future academic structures may differ.
- Women’s centers currently manage separate courses, participants, and operational data per center.
- Medical points currently serve their surrounding communities. Relationships between medical points and assigned schools are future scope.

---

## 4. Core architecture principles

### 4.1 Institution and semester are the main operational boundaries

For academic operations, the primary scope is:

> Institution + academic year + semester + operational period

For example:

> Student X is enrolled in Class A1 at School O during the first semester of the 2026–2027 academic year in the morning period.

The database must derive this context through relationships rather than repeatedly storing conflicting copies of all IDs on every record.

### 4.2 Permanent identities are not duplicated each semester

The system must distinguish permanent records from semester-specific facts.

| Permanent record | Semester-specific or historical record |
|---|---|
| Person identity and national ID | Student enrolment and class placement |
| Student personal profile | Institution, grade, class, and enrolment status |
| Staff personal profile | Institution position and role for a semester |
| Guardian identity | Active guardian relationship and its history |
| Institution identity | Institution-semester configuration |
| Subject definition | Class subject offering and teacher assignment |
| Asset identity | Custody, location, condition, or inventory movement |
| Document type | Issued document tied to a student enrolment and semester |

Historical facts must be preserved. The system must never overwrite a previous enrolment, staff position, class placement, approval, issued document, medical visit, or stock movement merely because a newer record exists.

### 4.3 Accounts, people, roles, and assignments are separate concepts

- A person is a real-world identity.
- A staff profile represents employment-related information.
- A guardian profile represents a person’s relationship to one or more students.
- An account provides authentication to a particular portal.
- A position assigns a staff member to an institution and organizational role.
- An assignment restricts a person to particular classes, subjects, courses, or periods.
- A permission authorizes an action.

A staff record must not automatically create a login account. Guards, for example, appear in staff lists and attendance but do not need portal access.

### 4.4 Backend authorization is mandatory

Hiding a page, button, institution, period, class, or student in the interface is not sufficient security. Every protected query and mutation must enforce authorization on the server.

### 4.5 Publication is separate from data entry

Internal data is not automatically visible to guardians. Marks, results, attendance details, documents, announcements, medical summaries, and comments require explicit publication rules.

### 4.6 Shared capabilities are implemented once

Authentication, authorization, audits, document generation, approvals, reporting, attachments, notifications, imports, exports, translations, and publication should be shared system engines rather than rebuilt independently in every portal.

---

## 5. Portals and authentication boundaries

The product has three separate authentication experiences.

| Portal | Account type | Main users |
|---|---|---|
| Admin Portal | Administrative account | System administrators and data administrators |
| Staff Portal | Staff account | Principals, deputies, secretaries, teachers, counselors, trainers, and center staff |
| Parent/Student Portal | Guardian account | Parents and authorized guardians |

Each portal should have its own:

- Login routes and page.
- Authentication guard and middleware.
- Password setup and recovery flow.
- Session boundary.
- Dashboard and layout.
- Authorization policies.
- Rate limits and security monitoring.
- Audit identification.

The same real person may eventually hold more than one account type, such as being both a staff member and a parent, but access must not silently cross from one portal to another.

### 5.1 Account security requirements

- Passwords must be securely hashed and never recoverable in plain text.
- Passwords must never be emailed or sent by SMS.
- First-time access and password recovery should use a short-lived verification code or secure setup link.
- Login and recovery pages should avoid confirming whether a national ID exists.
- Accounts can be activated, suspended, locked, and revoked independently from the underlying person or staff record.
- Sensitive actions may require password or PIN reconfirmation.
- Authentication events and sensitive account changes must be audited.
- Authorization must consider the current account, active position, institution, semester, period, role, assignment, and workflow state.

---

## 6. Academic time and operational scope

### 6.1 Academic year

An academic year is a centrally managed record such as `2026–2027`.

It should contain at least:

- Name and stable code.
- Start and end dates.
- Lifecycle status.
- Audit fields.

Suggested lifecycle states:

- `draft`
- `open`
- `closed`
- `archived`

### 6.2 Semester

An academic year contains an admin-defined number of semesters.

Usually there are two semesters, but a summer or exceptional third semester may be added. The schema must not enforce exactly two.

Each semester should contain:

- Academic year.
- Name and stable code.
- Sequence/order.
- Start and end dates.
- Lifecycle status.
- Audit fields.

### 6.3 Institution semester

An institution semester activates a global semester for one institution and acts as the operational container for its data.

Example:

> Academy of Hope 1 + First Semester + 2026–2027

It should support:

- Independent institutional preparation.
- Opening and closing.
- Reopening by authorized administrators.
- Archiving.
- Copying configuration from a previous institution semester.
- Tracking who performed each lifecycle action.

Only one semester should normally be current for an institution, although historical semesters remain readable.

### 6.4 Operational periods

In GCV terminology, a period is an operating shift within an institution semester, not a grading period.

Typical examples are:

| Period | Typical time |
|---|---|
| Morning | 08:00–11:00 |
| Afternoon | 11:00–14:00 |
| Optional third period | Defined by administrators |

Administrators determine the number, names, order, and times of periods. The schema must not enforce exactly two.

### 6.5 Semester lifecycle behavior

#### Draft

- Administrators define dates and periods.
- Classes, subjects, positions, assignments, and draft enrolments can be prepared.
- Previous-semester configuration can be copied.
- Ordinary attendance and marks cannot yet be recorded as official data.

#### Open

- Institution staff perform normal operations.
- Enrolment, attendance, marks, requests, and documents are processed.
- Only authorized users within the institution may edit operational records.

#### Closed

- Ordinary data entry stops.
- Reports and historical documents remain available.
- Corrections require a controlled correction workflow or authorized reopening.
- Closure should be blocked or warned when important work remains unresolved.

Possible closure checks include:

- Unverified attendance.
- Unsubmitted marks.
- Unapproved results.
- Pending transfers.
- Pending official documents.
- Incomplete required records.

#### Archived

- Records remain in the same database structure.
- Records are read-only under normal operation.
- Search, reporting, and document verification remain available.
- Archiving must not delete or move records into per-year SQL tables.

### 6.6 Copying a semester

Copying creates new draft records. It must never reuse operational rows from the source semester.

May be copied:

- Period definitions.
- Grade and class structures.
- Subject offerings.
- Proposed staff positions.
- Proposed teacher assignments.
- Timetable configuration.
- Document settings.
- Draft enrolments for continuing students.

Must not be copied as new facts:

- Attendance.
- Marks and published results.
- Medical visits.
- Counselor notes.
- Approvals and signatures.
- Issued documents.
- Formal requests.
- Completed transfers.
- Audit logs.

---

## 7. People and identity model

The system will represent several kinds of people:

- Students.
- Staff.
- Parents and guardians.
- Women’s-center beneficiaries.
- Medical-point beneficiaries.
- Other community beneficiaries in future modules.

Where practical, these profiles should be able to refer to one underlying real person without confusing the domain-specific records.

### 7.1 National IDs

- National ID is an important identity and login-related field but must not be the only internal identifier.
- Internal primary keys should remain stable even when a national ID is corrected.
- National IDs must be normalized, validated, and protected as sensitive information.
- Duplicate detection must not rely only on names.
- The Gaza civil registry may assist authorized data entry and validation, but it is not automatically the unquestionable authority over GCV records.

### 7.2 Contact information

- A person may have multiple phone numbers and email addresses.
- Contact points may be verified independently.
- A contact point should record ownership, type, verification status, and active/inactive history.
- Authentication recovery must only use verified, eligible contact points.

### 7.3 Guardian relationships

A student may have multiple parents or guardians. A guardian may be connected to multiple students.

The relationship must support:

- Relationship type.
- Legal or authorized guardian status.
- Start and end dates.
- Verification status.
- Portal access eligibility.
- Contact priority.
- Emergency contact status.
- Document evidence where required.
- Restrictions or notes with appropriate visibility.
- Complete relationship history.

Guardian relationships must not be represented as simple mutable `father_id` and `mother_id` fields only.

### 7.4 Sensitive information boundaries

Fields and records should be classified by visibility, for example:

- Public.
- Guardian-visible.
- Future student-visible.
- Institution staff.
- Counselor or safeguarding staff only.
- Medical staff only.
- HR only.
- Finance only.
- Central administration only.

Portal membership alone is not precise enough to protect sensitive data.

---

## 8. Roles, permissions, and record-level scopes

Access is calculated from more than a role name.

For staff operations, the effective authorization scope is:

> Authenticated staff account + active staff position + assigned institution + institution semester + assigned periods + role permissions + class/subject/course assignments + record state

### 8.1 Staff institution rule

- A staff member can work at only one institution at a time.
- A transfer closes the previous assignment and creates a new assignment.
- Historical assignments are preserved.
- Overlapping active assignments at different institutions must be rejected.
- A staff member may hold multiple compatible responsibilities inside the same institution.

### 8.2 Student institution rule

- A student can have only one active educational institution enrolment at a time.
- Historical enrolments are preserved.
- Transfers close or mark the previous enrolment as transferred and create a new enrolment after acceptance.
- Overlapping active institution enrolments must be rejected.

### 8.3 Secretary period scope

- A secretary is assigned to an institution semester and one or more operational periods.
- The current GCV arrangement assigns each secretary to all periods in one institution.
- The system must support a future secretary who can access only one period.
- Period restrictions must be enforced in backend queries and policies.

### 8.4 Assignment-based teacher access

A teacher cannot access all students or subjects merely because they have a teacher role.

For example, editing English marks for Class A1 requires:

- An active teacher position.
- The correct institution and semester.
- An English subject assignment.
- A Class A1 assignment.
- An open mark-entry window.
- A record state that permits editing.

### 8.5 Role overview

| Role | General scope |
|---|---|
| System administrator | Technical configuration, security, accounts, modules, audits, system health, and integrations |
| Data administrator | Institution setup, master data, imports, data quality, academic calendar, consolidated reports, and lifecycle management |
| Principal | Institution leadership, approvals, staff oversight, reports, transfers, formal requests, and publication |
| Deputy principal | Similar institutional leadership scope, with separately identifiable approvals and configurable limits |
| Secretary | Primary school data operation, verification, enrolment, attendance, documents, requests, and records |
| Counselor / consultant | Authorized student welfare, academic, family, displacement, orphan, and health-related information |
| Teacher | Assigned classes, assigned subjects, marks, and lead-class attendance where authorized |
| Women’s-center administrator | Center profile, beneficiaries, courses, trainers, attendance, assets, and reporting |
| Trainer | Assigned courses, participant lists, attendance, permitted outcomes, and course reports |
| Medical staff | Basic institution/staff access initially; clinical functions in a future release |
| Guard | Staff and attendance record only; no portal account required |

---

## 9. Admin Portal

The Admin Portal is not listed in the public portal selector. Its security must still rely on authentication, authorization, rate limits, and auditing—not on an undisclosed URL.

### 9.1 System administrator features

- Manage administrative accounts.
- Manage roles and permissions.
- Configure institution types and available modules.
- Manage authentication and security settings.
- Review audit records.
- Monitor system health, queues, failed jobs, storage, and integrations.
- Manage global templates and technical settings.
- Manage backup and restoration controls.
- Perform explicitly authorized exceptional intervention.

### 9.2 Data administrator features

- Create and configure institutions.
- Manage academic years, semesters, and institution-semester activation.
- Define or copy operational periods.
- Open, close, reopen, copy, and archive institution semesters.
- Manage shared reference/master data.
- Review and control imports.
- Detect duplicates and data-quality problems.
- View all institutions and consolidated reports.
- Request corrections from institutions.
- Configure organization-wide reporting and publication settings.
- Monitor semester completion and unresolved work.

### 9.3 Central authority limitation

Ordinary administrator access does not permit silent edits to institution-owned marks, attendance, student placement, staff records, or other operational facts.

The normal correction workflow is:

1. Central management flags a record.
2. The responsible institution receives a correction request.
3. The secretary corrects or disputes it.
4. The principal verifies the change when required.
5. Central management sees the resolution and audit history.

---

## 10. Parent/Student Portal

The Parent/Student Portal account belongs to the parent or authorized guardian. The guardian may use their national ID as the login identifier.

### 10.1 First-time password setup and recovery

1. The guardian submits their national ID.
2. The system checks for an eligible, verified guardian relationship and verified contact method.
3. The system sends a short-lived code or secure setup link.
4. The guardian verifies the code or link.
5. The guardian creates their own password.

The response must avoid exposing whether the national ID exists. A suitable generic message is:

> If the submitted information matches an eligible guardian account, verification instructions will be sent to the registered contact method.

### 10.2 Guardian features

- View all students connected through active, verified guardian relationships.
- Switch between multiple children.
- View permitted student basic information.
- View parent and guardian information.
- View orphan status and approved family information.
- View original and current displacement information when approved for guardian visibility.
- View current academic placement.
- View published subjects and assigned teachers.
- View published marks and results.
- View attendance information according to publication policy.
- View guardian-visible health summaries.
- View guardian-visible medical-point visit summaries in the future.
- View academy details and visiting hours.
- Submit correction requests instead of directly editing official data.
- Request official student documents.
- Track correction and document request status.
- Download issued documents and approved exports.
- Use the interface in Arabic or English.

### 10.3 Student correction workflow

1. Guardian submits the requested correction and supporting evidence.
2. Secretary reviews it.
3. Secretary accepts, rejects, or requests clarification.
4. Sensitive fields may require principal or higher approval.
5. Approved changes update the official record.
6. The guardian receives the decision.
7. The original request, evidence, decision, and applied change remain audited.

Guardians must never directly modify official student records.

### 10.4 Student document request workflow

1. Guardian requests a document.
2. Secretary reviews the request and verifies required data.
3. Principal or deputy approves/signs when required by document type.
4. The system generates an immutable issued document.
5. The guardian can download it.

Potential document types include:

- Proof of enrolment.
- School acceptance letter.
- Semester grade report.
- Semester attendance report.
- Student information summary.
- Transfer documentation.
- End-of-year certificate.

---

## 11. Staff Portal — schools and university spaces

Schools and university spaces share the education engine. Labels may differ in the interface without requiring a completely separate implementation.

### 11.1 Principal and deputy principal

Features include:

- View student profiles within the institution.
- View institution staff profiles.
- Manage institution staff within authorized boundaries.
- Monitor student and staff attendance.
- Review academic records, marks, and results.
- Review and approve records that require leadership approval.
- Apply audited electronic approval/signature.
- Review and send formal requests to GCV management.
- Manage incoming and outgoing student transfers.
- Accept or reject transfer requests.
- View and manage institution assets.
- Verify and monitor institution activities.
- Download authorized PDF and Excel reports.
- Access authorized financial reports without gaining unrestricted finance access.
- Publish approved results and documents where policy permits.

Principal and deputy remain separate roles even if their initial permissions are similar. The system must record who performed each action and should support future principal-only actions, delegation, or acting-leadership rules.

### 11.2 Secretary

The secretary is the primary operational user of the school module.

Features include:

- Create, read, and update student profiles within the assigned institution and periods.
- Manage guardian and parent relationships.
- Manage student enrolments and placement.
- Manage grades, sections, classes, subjects, and class records.
- Manage permitted staff information.
- Record staff attendance.
- Record student attendance directly.
- Review teacher-submitted student attendance.
- Monitor teacher marks submission.
- Perform initial verification and completeness checks on marks.
- Prepare student documents and formal records.
- Receive and process guardian correction requests.
- Prepare formal requests for principal/deputy approval.
- Coordinate student transfers.
- Run institution reports and exports.
- Perform authorized data-quality checks.
- Maintain institutional administrative records and archives.

The secretary can view closed and archived semesters as read-only. Ordinary editing is limited to an open semester and assigned periods.

### 11.3 Teacher

Features include:

- View assigned classes and subjects.
- View students enrolled in assigned classes.
- View only the permitted parts of student profiles.
- Enter marks only for assigned subject/class combinations.
- Save marks as drafts.
- Submit marks for verification.
- View submission, return, verification, approval, and publication status.
- Record attendance for an assigned lead/homeroom class.
- Submit attendance for secretary verification.
- View permitted schedules and teaching information.

Teachers must not:

- Browse unrelated students.
- Enter marks for unassigned subjects or classes.
- Edit verified or published marks without an authorized correction workflow.
- View restricted counselor, medical, HR, or finance data.

### 11.4 Counselor / consultant

Features include:

- View authorized student profile sections.
- View academic placement, grades, and attendance.
- View relevant health, disability, orphan, displacement, family, and social information.
- Add reports, observations, and follow-up notes.
- Track permitted follow-up history.
- Produce authorized welfare reports.

Counseling, safeguarding, medical, and ordinary administrative notes must be separate record types with explicit visibility. A generic unrestricted `notes` field is not acceptable.

### 11.5 Guards and other non-login staff

- Appear in staff profiles and staff lists.
- Have institution and position history.
- Participate in staff attendance.
- Appear in authorized reports.
- Do not require a login account, role, or portal permissions.

---

## 12. Academic management module

### 12.1 Academic structures

The module manages:

- Academic years.
- Semesters.
- Institution semesters.
- Operational periods.
- Academic levels/grades.
- Sections and classes.
- Subjects.
- Institution subject offerings.
- Teaching assignments.
- Lead/homeroom assignments.
- Assessment structures.
- Mark-entry windows.
- Results and publication.

### 12.2 Classes

A class belongs to one institution-semester period and one academic level.

Through the class relationship, the system can determine:

- Institution.
- Academic year.
- Semester.
- Period.
- Grade or academic level.
- Section/class.

### 12.3 Student enrolment and placement

Student enrolment is semester-specific and references the student’s class placement.

It should support statuses such as:

- Draft.
- Active.
- Completed.
- Promoted.
- Repeating.
- Transferred.
- Withdrawn.
- Suspended.
- Graduated.

A student must not have two conflicting active placements in the same semester.

### 12.4 Promotion and migration

Automatic promotion creates proposed future enrolments and never rewrites historical records.

At the end of an academic year:

1. The system calculates a promotion proposal.
2. It generates draft enrolments for the next year/grade.
3. The secretary reviews exceptions and class/period placement.
4. Principal/deputy approval occurs when required.
5. Approved enrolments become active when the new semester opens.

The process must handle:

- Promoted students.
- Repeating students.
- Graduates.
- Transfers.
- Withdrawals.
- Suspensions.
- Manual placement exceptions.
- Students awaiting decisions.

Between semesters in the same academic year, the usual proposal is the same grade and class unless a deliberate placement change is recorded.

### 12.5 Assessments, marks, and results

- Assessments belong to the correct institution semester, class, and subject context.
- Mark entry is restricted to assigned teachers and open entry windows.
- Marks support draft, submitted, returned, verified, approved, and published states as applicable.
- Verification and approval are separate actions.
- Published results are snapshots or versioned records; later corrections must not silently alter what was previously published.
- Guardians see only published information.
- Every correction must record the old value, new value, reason, actor, and approval where required.

Recommended workflow:

1. Teacher enters and saves draft marks.
2. Teacher submits marks.
3. Secretary checks completeness and verifies or returns them.
4. Principal/deputy approves the results.
5. Authorized user publishes the approved result set.
6. Guardians can view the published results.

---

## 13. Attendance

### 13.1 Student attendance

- Attendance belongs to an enrolled student, class, institution semester, period, and date through reliable relationships.
- The date must fall inside the semester.
- Only assigned lead teachers and authorized secretaries can enter attendance.
- Lead-teacher submissions require secretary verification before becoming official.
- A secretary may enter attendance directly.
- Changes after verification require a correction reason and audit record.
- Attendance publication to guardians is configurable.

Recommended workflow:

1. Lead teacher records attendance or secretary enters it directly.
2. Teacher submits the attendance sheet.
3. Secretary reviews, corrects when authorized, and verifies.
4. The verified record becomes official.

### 13.2 Staff attendance

- Covers all staff, including people without login accounts.
- Is institution- and semester-aware.
- Can be recorded by authorized secretaries or designated staff.
- Supports presence, absence, absence reason, arrival, departure, and permitted correction history.
- Reports can be filtered by institution, semester, period, staff role, and date range.

### 13.3 Attendance scanning

Computer-vision or QR-assisted attendance may be added as an input method, but automatically detected data must remain reviewable. Scanned values and manually confirmed official values should remain distinguishable where necessary.

---

## 14. Formal requests, verification, approval, and signing

### 14.1 Formal institutional requests

Institution staff can prepare and submit formal requests to GCV management.

Typical workflow:

1. Secretary prepares the request.
2. Principal/deputy reviews it.
3. Approver electronically signs it.
4. The request is sent to central management.
5. Management accepts, rejects, returns, or responds.
6. The institution tracks the status and response.

The workflow should support attachments, comments, due dates, status history, and responsible actors.

### 14.2 Verification, approval, and signature terminology

| Term | Meaning |
|---|---|
| Verification | A user checks that data is complete and correct |
| Approval | An authorized user authorizes a record, action, or publication |
| Electronic signature | The system records confirmation, actor, time, record version/hash, and decision |
| Cryptographic digital signature | Certificate-based signature applied to a document |

Most internal workflows currently require an audited electronic signature, not necessarily a legal cryptographic signature.

An electronic approval/signature should capture:

- Approver account.
- Confirmation through password, PIN, or equivalent secure step.
- Timestamp.
- Exact record version or content hash.
- Decision and comments.
- Revocation or supersession history.
- Device/IP metadata where appropriate and lawful.

### 14.3 Configurable approval levels

Principal approval should not be required for every ordinary field edit. Suggested rules are:

- Routine drafts: no principal approval.
- Student attendance: secretary verification.
- Marks: secretary verification, then principal/deputy approval before publication.
- Student transfers: principal/deputy approval.
- Formal management requests: principal/deputy signature.
- Official documents: approval based on document type.
- Sensitive identity or guardian changes: enhanced approval.
- Ordinary permitted corrections: secretary approval.

---

## 15. Documents, templates, reports, and exports

### 15.1 Shared document engine

All portals should use one shared document-generation engine supporting:

- Versioned document templates.
- Arabic and English templates.
- Institution branding and organization branding.
- PDF generation.
- Excel generation where appropriate.
- Approval requirements per document type.
- Unique document numbers.
- Public or authenticated verification codes.
- Immutable issued copies.
- Secure download permissions.
- Reissue, cancellation, and supersession history.

### 15.2 Semester-aware student documents

Issued academic documents should reference:

- Student.
- Student enrolment.
- Institution.
- Academic year.
- Semester.
- Period where relevant.
- Grade and class.
- Template version.
- Issue date and document number.
- Approver/signatory.
- Verification code.
- Immutable generated file.

A historical document must continue to show the institution and placement that were correct when it was issued.

### 15.3 Reports

Reports should support permission-aware filters such as:

- Institution.
- Institution type.
- Academic year.
- Semester.
- Operational period.
- Grade and class.
- Staff role.
- Subject.
- Date range.
- Record or workflow status.

Initial report families include:

- Student lists and profiles.
- Enrolment and placement.
- Student attendance.
- Staff attendance.
- Marks, assessments, and results.
- Missing or incomplete submissions.
- Transfers.
- Documents and requests.
- Staff positions.
- Institution assets.
- Women’s-center courses and attendance.
- Consolidated organization summaries.

Exports must enforce the same authorization and sensitive-data filtering as on-screen views.

---

## 16. Women’s Center module

Each women’s center currently manages its own operational dataset. Courses and center records are not shared between centers, while central management can view consolidated reports.

### 16.1 Center administration features

- View and manage basic center information.
- Create programmes and courses.
- Define course schedules and cohorts.
- Create and manage beneficiary profiles.
- Enrol beneficiaries in courses.
- Manage trainers and trainer assignments.
- Record or verify course attendance.
- Record permitted course outcomes and completion.
- Issue course certificates.
- Manage center assets.
- Produce reports and exports.

### 16.2 Trainer features

- View personal staff information.
- View assigned courses and schedules.
- View participant lists only for assigned courses.
- Record and submit course attendance.
- Add permitted course notes or results.
- View completion information.
- Download permitted course lists and reports.

Women’s-center courses should not be forced into school concepts such as grades, school classes, and school promotion unless a genuinely shared abstraction is identified.

---

## 17. Medical Point module

### 17.1 Initial scope

- Create and manage medical-point institution profiles.
- Assign doctors, nurses, and other staff.
- Maintain staff positions and attendance.
- Allow authorized staff to view basic institution information.

### 17.2 Future clinical scope

- Student and community-beneficiary lookup.
- Conditions, allergies, disabilities, and medications.
- Visits and treatments.
- Referrals.
- Follow-up dates.
- Attachments.
- Internal clinical notes.
- Guardian-visible summaries.
- Medical reports.
- Strict medical audit history.
- Future relationships between medical points and served schools.

Medical records and counselor records must have separate visibility and authorization boundaries.

---

## 18. Storage and inventory module

Each of the five storage units is an independent institution/location so the system can determine exactly where every item is held.

Future inventory features include:

- Item catalogue.
- Storage locations within units.
- Stock balances.
- Receipts.
- Issues.
- Returns.
- Transfers.
- Institution stock requests.
- Approval workflows.
- Damaged, missing, and expired items.
- Stock counts and reconciliations.
- Assets versus consumables.
- Lot, expiry, or serial tracking where required.
- Inventory reporting by unit and across GCV.

Stock levels must be derived from controlled movements or reconciled balances, not from unexplained direct quantity edits.

---

## 19. Assets and facilities

Institutions may manage physical assets such as chairs, tents, devices, equipment, furniture, and facilities.

The shared asset capability should support:

- Asset category and description.
- Owning institution.
- Current location.
- Serial or asset number where applicable.
- Acquisition information where permitted.
- Condition and status.
- Custodian or assignment.
- Transfers.
- Maintenance history.
- Damage, loss, retirement, and disposal.
- Attachments and photos.
- Audit history.

Permanent asset identity should not be duplicated each semester, though semester snapshots, assignments, or reports may reference it.

---

## 20. Shared platform services

The following capabilities should be designed as shared services:

- Authentication and account security.
- Roles, permissions, and record-level scoping.
- Institution and semester context.
- Audit history.
- Arabic/English localization and RTL/LTR support.
- Document templates and generation.
- PDF and Excel export.
- Import, validation, preview, and error reporting.
- Attachments and secure downloads.
- Notifications.
- Formal requests and approval workflows.
- Electronic approval/signature.
- Publication.
- Search.
- Report filtering.
- Settings and controlled reference data.
- Queue and job status.
- Data-quality checks.
- Archive and retention rules.
- Public document verification.
- Backup and restoration.

---

## 21. Audit, history, and correction requirements

Important tables should record creation, update, soft deletion where appropriate, and the responsible account.

Audit design must capture more than `updated_at`. For sensitive records, it should be possible to answer:

- Who changed the record?
- What changed?
- What were the previous and new values?
- When did the change happen?
- Which account and portal performed it?
- Why was it changed?
- Was it part of an approval, correction, import, or automated job?
- Which institution and semester did it affect?

Rules:

- Issued documents are immutable; corrected documents are reissued or superseded.
- Published results retain publication/version history.
- Approvals and signatures cannot be silently edited.
- Historical enrolments and positions remain available.
- Sensitive corrections require reasons.
- Soft deletion is not a substitute for domain statuses such as withdrawn, transferred, cancelled, or inactive.
- Audit access itself must be permission-controlled.

---

## 22. Data validation and integrity

The application and database should enforce, where practical:

- Stable internal identifiers.
- Unique normalized national IDs when applicable.
- Academic year start before end.
- Semester dates within the academic year.
- Period start time before end time.
- Classes attached to the correct institution semester and period.
- Attendance dates inside the relevant semester.
- Marks tied to the correct enrolment, assessment, class, subject, and semester.
- No overlapping active staff assignments at different institutions.
- No overlapping active student enrolments at different institutions.
- No conflicting active student placements in one semester.
- No teacher access outside assigned class/subject combinations.
- No ordinary mutations in archived semesters.
- Restricted changes to dates after a semester opens.
- Controlled values through reference tables or validated enums where appropriate.
- Import validation before records become official.

Database constraints, transactions, unique indexes, foreign keys, and service-level validation should work together. Business-critical integrity must not depend only on the frontend.

---

## 23. Localization and usability

- The product must support Arabic and English.
- Arabic pages must support right-to-left layout correctly.
- Translatable interface labels should not be hard-coded throughout templates.
- Institution-specific terminology may vary, especially between schools, university spaces, and women’s centers.
- Dates, numbers, names, and exported documents must render correctly in both languages.
- The secretary interface should prioritize fast, familiar, table-oriented data entry where appropriate.
- Large datasets require pagination, server-side filtering, indexed search, and background exports.
- Users should see understandable validation and workflow errors rather than raw system messages.

---

## 24. Brand and interface design system

This section defines the initial visual direction for GCV DATA. It is sufficient to begin product design and implementation, but it is not yet a complete corporate identity manual.

### 24.1 Brand colors

| Token | Hex | Intended use |
|---|---|---|
| Primary teal | `#254151` | Primary navigation, page headers, primary actions, active states, and strong brand surfaces |
| Secondary gold | `#EEC219` | Highlights, selected accents, badges, key indicators, and controlled calls to attention |
| Off-white | `#EAEAE8` | Light page backgrounds, muted panels, separators, and light text on dark surfaces |
| Dark gray | `#616153` | Secondary text, subdued icons, borders, and neutral interface elements |
| Red | `#D55342` | Error, rejection, destructive actions, overdue or critical states |
| Green | `#518245` | Success, approval, verified, present, completed, or healthy states |
| Black | `#000000` | High-emphasis text and monochrome output where appropriate |

These meanings should remain consistent across the Admin, Staff, and Parent/Student portals. Institution types may use restrained secondary accents, but they must not replace the shared GCV identity or give the impression of separate products.

### 24.2 Semantic color rules

- Teal is the default application color and should carry most structural weight.
- Gold is an accent, not a default body-text color and not a large page-background color.
- Green indicates successful, accepted, approved, verified, or completed states.
- Red indicates errors, rejection, destructive actions, or critical warnings.
- Neutral states use off-white, dark gray, and carefully defined lighter/darker neutral variants.
- Workflow meaning must never depend on color alone. Pair colors with text, icons, and status labels.
- Destructive actions must require clear wording and appropriate confirmation, regardless of color.
- Status colors must be applied consistently in tables, dashboards, forms, reports, and exported documents.

The supplied colors are brand anchors, not a complete UI palette. The implementation may derive lighter and darker tints for backgrounds, borders, hover states, focus states, and disabled states, but those derived colors must remain documented as design tokens and must be tested for accessibility.

### 24.3 Accessibility and contrast

- Normal text should meet WCAG AA contrast of at least `4.5:1`; large text and essential graphical elements should meet their applicable contrast requirements.
- Primary teal with off-white or white provides strong contrast and is suitable for navigation and primary branded surfaces.
- Gold does not provide enough contrast as normal text on white or off-white. Use it as a decorative accent, or place gold text/icons on a sufficiently dark background such as primary teal or black.
- Red should not be used for small normal text on white without a darker accessible text variant. The supplied red remains suitable as an accent, icon, border, or large-text color when contrast is verified.
- Green is borderline for normal text on white and insufficient on off-white. Use a darker derived green for small status text when necessary.
- Focus indicators must remain visible in both light and dark themes and must not rely only on a subtle color change.
- Forms, tables, charts, and validation states must remain understandable for users with color-vision deficiencies.

### 24.4 Typography

| Role | Font | Usage |
|---|---|---|
| Primary typeface | League Spartan | Product identity, page titles, section headings, dashboard metrics, and prominent labels |
| Secondary typeface | Montserrat | Body text, forms, tables, navigation details, buttons, reports, and dense operational interfaces |

Typography rules:

- Use League Spartan selectively. Dense tables and long paragraphs should use Montserrat for readability.
- Arabic text must use an Arabic typeface selected to visually complement the brand fonts; League Spartan and Montserrat must not be assumed to provide complete, high-quality Arabic support.
- The final Arabic font must support the required Arabic glyphs, weights, numerals, punctuation, and PDF embedding.
- Define font fallbacks so the interface remains usable when web fonts fail to load.
- Use a consistent type scale and avoid excessive heading sizes in data-heavy screens.
- Exported PDFs and generated documents must embed or package permitted font files to avoid layout changes between environments.
- Font licenses and redistribution rights must be verified before bundling font files with the application or generated documents.

### 24.5 Logo asset and usage

The supplied master logo is a transparent PNG containing:

- The GCV house-and-children symbol.
- The English organization name, “THE GAZA CHILDREN VILLAGE.”
- The organization website and email address.
- A white, gold, and green treatment intended primarily for dark backgrounds.

Initial usage rules:

- Preserve the logo’s aspect ratio and clear space.
- Never stretch, recolor, rotate, outline, or add effects to the logo.
- Use the full supplied lockup on dark backgrounds and at sizes where the organization name and contact details remain readable.
- Do not use the full contact-detail lockup as a small navigation logo, favicon, mobile icon, or compact button mark.
- Provide meaningful alternative text such as `Gaza Children Village` in web interfaces. Decorative repetitions may use empty alternative text.
- Generated official documents should use an approved high-resolution or vector logo and maintain consistent placement.
- Store the canonical asset in the repository under a predictable path such as `resources/brand/gcv-logo-dark.png` or an equivalent framework-appropriate location.

Before final production polish, GCV should provide or approve:

- A light-background logo variant using dark lettering.
- A simplified horizontal wordmark without contact details.
- A symbol-only mark for the app icon and favicon.
- Preferably SVG/vector originals for crisp scaling and print output.
- Minimum size and clear-space rules.

The attached PNG is enough to begin the login page, branded empty states, document prototypes, and general visual direction. Temporary UI implementations must keep the asset replaceable when the additional approved variants become available.

### 24.6 Layout and component direction

- The application should feel professional, calm, trustworthy, and operational rather than playful or decorative.
- Data-heavy screens should prioritize readable tables, strong filters, bulk actions, clear workflow status, and fast keyboard-friendly entry.
- Use primary teal for the main shell and key actions; keep work surfaces predominantly white or off-white.
- Use gold sparingly for emphasis so it retains meaning.
- Cards should not replace tables where users need comparison, scanning, sorting, or bulk work.
- Important statuses should appear as labeled badges with icons where helpful.
- Approval, rejection, verification, publication, and archival actions must be visually distinct.
- Mobile layouts should preserve critical actions without hiding permission or workflow context.
- Arabic RTL and English LTR layouts must both be treated as first-class designs rather than simple text-direction flips.

### 24.7 Initial implementation tokens

The frontend should define centralized tokens rather than scattering literal colors and font names throughout templates. A starting CSS representation is:

```css
:root {
    --gcv-color-primary: #254151;
    --gcv-color-secondary: #EEC219;
    --gcv-color-surface-muted: #EAEAE8;
    --gcv-color-neutral-dark: #616153;
    --gcv-color-danger: #D55342;
    --gcv-color-success: #518245;
    --gcv-color-black: #000000;

    --gcv-font-display: "League Spartan", sans-serif;
    --gcv-font-body: "Montserrat", sans-serif;
}
```

Derived tokens should later be added for text, surfaces, borders, focus rings, hover/pressed states, disabled states, and semantic status backgrounds. Components should consume semantic tokens such as `--color-action-primary` or `--color-status-success-text` rather than deciding color meaning independently.

### 24.8 Design deliverables still required

Before calling the design system complete, create and approve:

- Logo variants and vector assets.
- Arabic companion font.
- Full typography scale and font weights.
- Spacing, radius, elevation, border, and icon rules.
- Button, input, select, table, modal, alert, badge, tab, and navigation specifications.
- Light and dark surface combinations actually used by the application.
- Responsive breakpoints and page-shell layouts.
- Accessibility-tested semantic color variants.
- PDF/report and official-document templates.
- Loading, empty, error, offline, and permission-denied states.

---

## 25. Notifications

The shared notification service may support in-app notifications first and external delivery channels later.

Notification events can include:

- Guardian password setup or recovery.
- Correction request status.
- Document request status.
- Formal request submission or response.
- Attendance awaiting verification.
- Marks awaiting submission, verification, or approval.
- Returned work requiring correction.
- Semester closure warnings.
- Transfer requests.
- Publication of results or documents.
- Future medical follow-up reminders.

Notifications must not expose sensitive data in insecure channels.

---

## 26. Import and export

### 26.1 Imports

- Support staged imports rather than writing unreviewed rows directly into official tables.
- Validate required fields, references, duplicates, and permission scope.
- Show row-level errors.
- Allow preview before commitment.
- Record who imported the data and which source file was used.
- Produce a result summary.
- Use transactions or recoverable batches.
- Maintain mapping from source rows to created or updated records.

### 26.2 Exports

- PDF for human-readable official reports and documents.
- Excel for authorized structured reports and operational work.
- Background jobs for large exports.
- Download status and expiry where necessary.
- The export must reflect the user’s exact institution, semester, period, and field permissions.

---

## 27. Technical implementation direction

The preferred implementation direction is a modern, modular **Laravel** application using a relational database such as MySQL/MariaDB. It should begin as a modular monolith unless measured needs justify separate services.

Recommended boundaries include modules for:

- Identity and people.
- Accounts and authentication.
- Authorization.
- Organizations and institutions.
- Academic calendar.
- Staff and positions.
- Students and guardians.
- Enrolment and placement.
- Classes, subjects, and assignments.
- Attendance.
- Assessments, marks, and results.
- Workflows and approvals.
- Documents and reports.
- Women’s centers.
- Medical points.
- Inventory and assets.
- Auditing and system administration.

### 27.1 Implementation expectations

- Use explicit foreign keys and indexes.
- Use transactions for multi-record workflows.
- Keep domain logic out of controllers and UI components.
- Use policies or equivalent server-side authorization for every protected resource.
- Use background queues for imports, large exports, document generation, and notifications.
- Avoid one database table per institution, year, or semester.
- Avoid copying permanent person records between semesters.
- Avoid hard-coded role checks scattered across the codebase.
- Avoid placing all staff capabilities into one large controller or service.
- Expose stable application services or APIs that future clients can use.
- Write automated tests for scope isolation, permissions, lifecycle transitions, and critical workflows.

### 27.2 Offline operation and synchronization

Earlier SchoolDesk planning considered an offline-first desktop application with a local SQLite database and protected central synchronization. This remains a possible direction, but the final synchronization architecture, conflict model, desktop packaging, and offline scope are not yet confirmed for GCV DATA.

Do not generate a multi-database synchronization system until the following are explicitly designed:

- Source of truth.
- Record identifiers.
- Change log/outbox model.
- Conflict detection and resolution.
- Authentication while offline.
- Attachment synchronization.
- Schema migration coordination.
- Retry and idempotency rules.
- Data encryption and device revocation.

The first web implementation should keep domain services clean enough to support a future offline client without assuming that offline synchronization already exists.

---

## 28. Security and privacy requirements

- Follow least privilege.
- Enforce institution, semester, period, class, subject, course, and field-level scopes on the server.
- Protect national IDs, health information, counselor notes, family information, HR data, and finance data.
- Use secure session and cookie settings.
- Apply CSRF protection to browser actions.
- Rate-limit login, recovery, verification, and public document endpoints.
- Encrypt transport using HTTPS.
- Protect files with authorization; do not rely on guess-resistant URLs alone.
- Validate uploaded file type, size, and access.
- Log security-sensitive actions.
- Avoid leaking account existence through login recovery.
- Provide account suspension and session revocation.
- Separate medical, counselor, safeguarding, HR, and finance visibility.
- Establish backup, restore testing, and retention procedures before production use.
- Avoid storing secrets in the repository.

Security-sensitive code generated by AI tools must be reviewed by a developer and tested before use.

---

## 29. Release plan

The system should be delivered incrementally. Shared foundations may be implemented before the first full user-facing portal.

### Foundation release

Only the administration necessary to support the first module:

- Authentication foundations.
- Account separation.
- Roles and permissions.
- Institution and semester scoping.
- Audit infrastructure.
- Localization foundations.
- Queue infrastructure.
- Basic settings and internal data-management screens.

This is not the full Admin Portal.

### Release 1 — Parent/Student Portal

- Guardian authentication and password setup.
- Guardian–student relationships.
- Multiple-child selection.
- Student basic information.
- Guardian and family information.
- Orphan and displacement information.
- Academic placement.
- Published subjects, teachers, marks, and results.
- Published attendance.
- Guardian-visible health summaries.
- Academy information.
- Correction requests.
- Document requests and downloads.
- Arabic and English interface.

Seeded data or minimal internal screens may support this release until the Academies Portal is complete.

### Release 2 — Academies and University Spaces Portal

Suggested internal phases:

1. Institution-semester configuration.
2. Staff and positions.
3. Students and guardians.
4. Enrolment and placement.
5. Grades, sections, classes, and subjects.
6. Teaching assignments.
7. Student and staff attendance.
8. Assessments and marks.
9. Result review, approval, and publication.
10. Student behavior and welfare boundaries.
11. Documents, requests, reports, and exports.
12. Assets and facilities.

### Release 3 — Medical Point Portal

- Medical staff and access.
- Beneficiary lookup.
- Health conditions and medication.
- Visits and treatments.
- Referrals and follow-ups.
- Attachments and internal notes.
- Guardian-visible summaries.
- Medical reports and strict audit history.

### Release 4 — Storage Portal

- Catalogue and locations.
- Stock balances and movements.
- Requests and approvals.
- Transfers, returns, and counts.
- Damaged, missing, and expired stock.
- Assets versus consumables.
- Inventory reporting.

### Release 5 — Women’s Center Portal

- Beneficiary files.
- Programmes, courses, and cohorts.
- Trainers and assignments.
- Enrolment and attendance.
- Activities and outcomes.
- Certificates.
- Referrals and approved case-management functions.
- Programme reporting.

### Release 6 — Full Admin Portal and organization reporting

- Complete account administration.
- Roles and permissions management.
- Institution configuration and module activation.
- Master data.
- Document-template management.
- Audit inspection.
- Cross-institution reports.
- Data-quality management.
- Import/export management.
- Central dashboards.
- System health and queue status.
- Backup and restoration controls.

---

## 30. Confirmed business rules

The following should be treated as approved baseline requirements:

1. GCV is the top-level organization.
2. All 19 institutions belong to GCV.
3. Institutions are operationally independent.
4. Central management can view institution data but does not ordinarily edit institution-owned operational records.
5. Institution type controls available modules.
6. Storage units are independent institutions/stock locations.
7. University spaces use the shared academic engine when operating academically.
8. Medical points initially manage institution and staff information; clinical functionality follows later.
9. Women’s centers currently manage separate courses and beneficiaries per center.
10. Academic operations are scoped by institution, academic year, semester, and operational period.
11. Administrators define any number of semesters and periods.
12. Permanent student and staff profiles are not duplicated each semester.
13. Staff positions, student enrolments, class placements, teaching assignments, attendance, marks, results, and academic documents preserve semester context.
14. A staff member cannot work at multiple institutions at the same time.
15. A student cannot be actively enrolled in multiple educational institutions at the same time.
16. Historical enrolments and staff positions are preserved.
17. Promotion generates draft future enrolments instead of changing historical rows.
18. A staff record does not automatically provide login access.
19. Staff permissions depend on active positions and assignments, not only role names.
20. Secretaries can be limited to assigned operational periods.
21. Teachers can access only assigned classes and subjects.
22. Guardian accounts belong to guardians, not students.
23. Guardians cannot directly edit official student records.
24. Internal records are separate from guardian-visible published data.
25. Teacher-submitted attendance and marks are not automatically official.
26. Verification, approval, publication, and electronic signature are distinct actions.
27. Historical documents and published results must remain reproducible.
28. Sensitive domains require field- and record-level visibility boundaries.
29. Important actions and corrections require complete audit history.
30. Arabic and English interfaces are required.

---

## 31. Unresolved decisions

The following items must not be silently decided by a code generator:

1. Final database schema and exact table names.
2. Whether all non-academic institutions must use the academic semester as their reporting cycle, or only staff assignments and selected operations do.
3. The final unified person/identity model across students, staff, guardians, and beneficiaries.
4. Exact guardian verification evidence and legal access rules.
5. Exact permission catalogue and whether direct user overrides are allowed.
6. Which actions require principal-only approval versus deputy approval.
7. Final assessment types, grading formulas, pass rules, and result calculation.
8. Attendance status catalogue and lateness rules.
9. Exact student transfer and migration policy.
10. Which student fields are guardian-visible.
11. Counselor and safeguarding visibility policy.
12. Medical-point service relationships with schools.
13. Women’s-center verification and case-management rules.
14. Inventory costing and finance integration.
15. Legal requirements for electronic versus cryptographic signatures.
16. Notification providers and delivery channels.
17. Hosting, deployment, backup, disaster recovery, and retention details.
18. Final offline-first and synchronization architecture.
19. Exact public document verification behavior.
20. Whether and how external civil-registry data is refreshed.

These decisions should be resolved through focused design documents before related production code is generated.

---

## 32. Confirmed future requirements

These requirements are confirmed but must not be implemented until their owning module PR is explicitly approved. Record them here to prevent conflicting design decisions in earlier modules.

### 32.1 Excel import engine

All future Excel imports must use staged, reviewed, explicitly confirmed imports. No direct unreviewed Excel-to-production-table writes are permitted.

#### Import modes

The import engine must support two explicitly named modes:

1. **`insert_only`**
   - Create rows that do not already exist according to an approved stable matching key.
   - Skip matching existing records.
   - Report every created, skipped, and invalid row.
   - Do not call this behavior "patch importing" in technical documentation.

2. **`patch_existing`**
   - Match existing records using an approved stable matching key.
   - Update only explicitly permitted/importable fields.
   - Skip rows with no existing match.
   - Report old and new proposed values before commitment.

#### Required import pipeline

Every import must include:

- Uploaded-file registration.
- Staged rows with schema and header validation.
- Normalized matching keys.
- Duplicate detection within the file.
- Preview/dry run.
- Row-level errors and warnings.
- Explicit confirmation before official writes.
- Transactional or recoverable batches.
- Idempotency protection.
- Import actor and operational scope.
- Created/skipped/updated/failed row counts.
- Downloadable result report.
- Institution, semester, period, permission, and field-level enforcement.

Do not create import tables, services, routes, jobs, or modules before this feature is approved in its own PR.

### 32.2 Gaza civil-registry lookup for student registration

When an authorized user registers a student in a future Student module:

1. The user enters the student's national ID.
2. The ID is normalized and validated.
3. The system queries the Gaza civil-registry reference source through a deliberate application contract/service — not by reaching directly into a raw registry table throughout the codebase.
4. A matching registry record may suggest or autofill approved identity fields.
5. The user reviews the suggestions before saving the student.
6. The civil-registry source is advisory, not unquestionable authority.
7. A missing match must not prevent authorized manual student registration.
8. A registry match must not automatically overwrite an existing person or student record.
9. Conflicting data must be shown for review and handled through a controlled correction/identity-resolution workflow.
10. The student uses a stable synthetic internal primary key. National ID is an identifier, not the student table primary key.
11. National IDs and registry data are sensitive and require masking, permission controls, safe logging, and audit redaction.
12. The system should preserve data provenance where useful: registry-suggested, user-entered, imported, or later corrected.
13. The exact physical table name for the civil-registry source is **unresolved** and must not be assumed to be `gaza_civil_records` until the existing dataset and schema are reviewed.

Do not create civil-registry migrations, models, adapters, student tables, lookup endpoints, or registration UI before this feature is approved in its own PR.

---

## 33. Instructions for developers and AI coding tools

When implementing from this specification:

1. Do not generate the entire system in one pass.
2. Select one release and one bounded module.
3. Identify the confirmed rules that apply to that module.
4. Identify unresolved decisions and ask before choosing a consequential design.
5. Produce a small domain model and workflow before migrations or UI code.
6. Define authorization rules for every read and mutation.
7. Include institution and semester scope in queries and tests.
8. Preserve historical records instead of overwriting them.
9. Separate draft, submitted, verified, approved, rejected, and published states where applicable.
10. Add database constraints for critical invariants.
11. Add automated tests for permissions, cross-institution isolation, period restrictions, and lifecycle rules.
12. Keep Arabic/RTL behavior in scope from the beginning.
13. Do not implement an unresolved feature as if it were approved.
14. Do not expose sensitive fields merely because the user can access the same student or institution.
15. Do not confuse account type, staff role, position, assignment, and permission.

### Required implementation output for each module

Before merging a module, provide:

- Functional scope.
- Actors and permissions.
- Domain entities and relationships.
- Workflow states and transitions.
- Database migrations and constraints.
- Backend services/actions.
- Authorization policies.
- Routes or API contract.
- UI screens and validation.
- Audit events.
- Notifications where applicable.
- Automated tests.
- Seed or fixture data.
- Migration/import notes.
- Explicit exclusions and remaining questions.

---

## 34. Suggested first engineering tasks

The safest implementation order is:

1. Repository conventions, environments, and automated test setup.
2. Organization, institution types, and institutions.
3. Academic years, semesters, institution semesters, and operational periods.
4. People, national IDs, and contact points.
5. Separate admin, staff, and guardian account models/guards.
6. Roles, permissions, positions, assignments, and scoped authorization.
7. Audit infrastructure.
8. Student, guardian relationship, and enrolment foundations.
9. Publication and document-request foundations.
10. First vertical Parent/Student Portal feature using seeded or minimally managed data.

Each task should be implemented as a complete, tested vertical slice rather than generating all database tables first without verified workflows.

---

## 35. Definition of success

GCV DATA succeeds when:

- Each institution can manage its own authorized operations without seeing or editing unrelated institutions.
- Central management can obtain reliable organization-wide visibility and reports without becoming the ordinary editor of local records.
- Semester history remains accurate and reproducible.
- Staff access matches their real institution, role, period, class, subject, or course assignment.
- Guardians can securely access approved student information and request changes or documents without directly modifying official records.
- Reports, approvals, issued documents, and published results are traceable.
- Sensitive student, medical, counselor, HR, and finance information is properly separated.
- The product works naturally in Arabic and English.
- New modules for medical points, storage units, and women’s centers can be added through shared platform services without corrupting the school model.
- Developers can extend the system without rewriting its identity, institution, semester, authorization, and audit foundations.
