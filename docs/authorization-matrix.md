# Authorization Matrix (F19 + SR-6)

Generated from: `Modules/Authorization/Data/PermissionKey.php` and
`Modules/Authorization/Database/Seeders/PermissionCatalogueSeeder.php`.

Each cell: ✅ allowed | — not granted

## Legend

| Column         | Role code         |
|----------------|-------------------|
| SYS_ADM        | system_admin      |
| AUD_INS        | audit_inspector   |
| CAL_MGR        | calendar_manager  |
| ACC_MGR        | account_manager   |
| INST_ADM       | institution_admin |
| PRINCIPAL      | principal         |
| DEP_PRIN       | deputy_principal  |
| SECRETARY      | secretary         |
| TEACHER        | teacher           |
| COUNSELOR      | counselor         |
| OPS_VWR        | operations_viewer |
| STF_MGR        | staff_manager     |

## Institution Permissions

| Permission                  | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|-----------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| institution.view            | ✅      | ✅      | ✅      | —       | ✅       | ✅        | ✅       | ✅        | ✅      | ✅        | ✅      | ✅      |
| institution.create          | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| institution.update          | ✅      | —       | —       | —       | ✅       | —         | —        | —         | —       | —         | —       | —       |
| institution.toggle_active   | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | —       |

## Academic Calendar Permissions

| Permission                      | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|---------------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| academic_year.view              | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | ✅      | —       |
| academic_year.manage            | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| semester.view                   | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | ✅      | —       |
| semester.manage                 | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| institution_semester.view       | ✅      | —       | ✅      | —       | ✅       | ✅        | ✅       | ✅        | ✅      | ✅        | ✅      | —       |
| institution_semester.open       | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| institution_semester.close      | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| institution_semester.archive    | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| operational_period.view         | ✅      | —       | ✅      | —       | ✅       | ✅        | ✅       | ✅        | ✅      | ✅        | ✅      | —       |
| operational_period.manage       | ✅      | —       | ✅      | —       | —        | —         | —        | —         | —       | —         | —       | —       |

## Staff Permissions

| Permission                 | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|----------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| staff_profile.view         | ✅      | ✅      | —       | —       | ✅       | ✅        | ✅       | ✅        | —       | —         | ✅      | ✅      |
| staff_profile.create       | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | ✅      |
| staff_profile.update       | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | ✅      |
| staff.assign               | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | ✅      |
| staff.transfer             | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | ✅      |
| staff_position.assign      | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | ✅      |
| staff_position.end         | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | ✅      |
| staff_position.view        | ✅      | —       | —       | —       | ✅       | ✅        | ✅       | ✅        | —       | —         | ✅      | ✅      |

## People Permissions

| Permission               | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|--------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| person.view              | ✅      | ✅      | —       | ✅      | ✅       | ✅        | ✅       | ✅        | ✅      | ✅        | —       | ✅      |
| person.create            | ✅      | —       | —       | —       | —        | —         | —        | ✅        | —       | —         | —       | ✅      |
| person.update            | ✅      | —       | —       | —       | —        | —         | —        | ✅        | —       | —         | —       | ✅      |
| person.view_sensitive    | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | ✅        | —       | —       |

## Account Permissions

| Permission            | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|-----------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| account.view          | ✅      | —       | —       | ✅      | ✅       | ✅        | —        | —         | —       | —         | —       | —       |
| account.create        | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| account.suspend       | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| account.lock          | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| account.revoke        | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| account.role_assign   | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| account.role_revoke   | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |

## Audit Permissions

| Permission     | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|----------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| audit.view     | ✅      | ✅      | —       | —       | —        | —         | —        | —         | —       | —         | ✅      | —       |
| audit.export   | ✅      | ✅      | —       | —       | —        | —         | —        | —         | —       | —         | —       | —       |

## System Permissions

| Permission                 | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|----------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| system.settings_view       | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| system.settings_update     | ✅      | —       | —       | —       | —        | —         | —        | —         | —       | —         | —       | —       |
| role.view                  | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |
| role.assign                | ✅      | —       | —       | ✅      | —        | —         | —        | —         | —       | —         | —       | —       |

## Civil Registry Permissions

| Permission               | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|--------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| civil_registry.lookup    | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | —         | —       | —       |

## Student Registry Permissions

| Permission                    | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|-------------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| student.view                  | ✅      | ✅      | —       | —       | ✅       | ✅        | ✅       | ✅        | —       | ✅        | ✅      | —       |
| student.view_restricted       | ✅      | —       | —       | —       | —        | —         | —        | —         | ✅      | —         | —       | —       |
| student.create                | ✅      | —       | —       | —       | —        | ✅        | —        | ✅        | —       | —         | —       | —       |
| student.update                | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | ✅        | —       | —       |
| student.manage                | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |

## Guardian Relationship Permissions

| Permission                       | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|----------------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| guardian_relationship.view       | ✅      | —       | —       | —       | ✅       | ✅        | ✅       | ✅        | —       | ✅        | —       | —       |
| guardian_relationship.manage     | ✅      | —       | —       | —       | —        | ✅        | —        | ✅        | —       | —         | —       | —       |
| guardian_relationship.verify     | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |

## Academic Structure Permissions

| Permission                  | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|-----------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| academic_level.manage       | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |
| classroom.manage            | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |
| class_group.manage          | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |
| subject.manage              | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |
| subject_offering.manage     | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | —         | —       | —       |

## Enrolment Permissions

| Permission               | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|--------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| enrollment.view          | ✅      | ✅      | —       | —       | ✅       | ✅        | ✅       | ✅        | ✅      | ✅        | ✅      | —       |
| enrollment.manage        | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | —         | —       | —       |
| enrollment.transfer      | ✅      | —       | —       | —       | —        | ✅        | ✅       | —         | —       | —         | —       | —       |
| enrollment.promote       | ✅      | —       | —       | —       | —        | ✅        | ✅       | —         | —       | —         | —       | —       |

## Import Pipeline Permissions

| Permission      | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|-----------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| import.upload   | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | —         | —       | —       |
| import.review   | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | —         | —       | —       |
| import.apply    | ✅      | —       | —       | —       | —        | ✅        | ✅       | ✅        | —       | —         | —       | —       |

## Sensitive Data Export Permissions

| Permission               | SYS_ADM | AUD_INS | CAL_MGR | ACC_MGR | INST_ADM | PRINCIPAL | DEP_PRIN | SECRETARY | TEACHER | COUNSELOR | OPS_VWR | STF_MGR |
|--------------------------|---------|---------|---------|---------|----------|-----------|----------|-----------|---------|-----------|---------|---------|
| data.sensitive_export    | ✅      | —       | —       | —       | —        | ✅        | —        | —         | —       | ✅        | —       | —       |

## Important Policy Rules

1. **No implicit super-admin**: `system_admin` must have the permission explicitly in the role-permissions table. Removing it from the seeder removes access.
2. **Closed/archived semester gate**: Write operations (assign, manage, etc.) are blocked by the PolicyKernel when `semesterStatus` is `closed` or `archived`. Read-only operations (*.view, *.export) are exempted.
3. **No inferred teaching access**: A `teacher` position grants the `teacher` role, which gives `institution_semester.view`, `operational_period.view`, `person.view`, and `student.view_restricted` only. No mark or assessment access is implied by a position alone.
4. **Guardian portal**: Guardians are not granted any role in this matrix. They interact through a separate, purpose-limited guardian API (F24+).
5. **Mutual-exclusion enforcement**: `principal` and `deputy_principal` cannot be held simultaneously by the same person in the same institution/semester interval (F16).
6. **Student view vs view_restricted**: `student.view` grants full profile access; `student.view_restricted` grants only the subset needed for a teacher's class roster (name, attendance) with no sensitive fields.
7. **Import apply gate**: `import.apply` must be held alongside `student.create` (or `student.manage`) for the apply action to complete; the pipeline checks both.
