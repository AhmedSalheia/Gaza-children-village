<?php

declare(strict_types=1);

namespace Modules\Documents\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the `document_type_catalogue` reference table.
 *
 * This seeder is idempotent: it uses INSERT OR IGNORE semantics (updateOrInsert)
 * so it may be run multiple times without creating duplicate rows.
 *
 * Catalogue entries must never be deleted or have their code changed.
 * New types are appended; label or metadata corrections use updateOrInsert.
 *
 * Each entry declares:
 *   - label_ar / label_en
 *   - required_context_keys: dot-key paths from DocumentDataContext that must be
 *     non-empty before a document of this type may be issued
 *   - completeness_checks: domain preconditions checked at issuance time
 *   - approval_required: whether a principal must countersign before issuance
 *   - allowed_requesters: which portal roles may initiate a request
 *   - template_family: groups document type variants for template lookup
 *   - validity_days: null = no expiry; integer = days until the document expires
 *   - public_verification: true if the issued document carries a public QR/URL
 *   - reissuable: true if a replacement may be issued after revocation
 *   - display_order: UI sort order
 */
final class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $now = now()->toDateTimeString();
        $types = $this->catalogue();

        foreach ($types as $type) {
            $type['required_context_keys'] = json_encode($type['required_context_keys'], JSON_UNESCAPED_UNICODE);
            $type['completeness_checks'] = json_encode($type['completeness_checks'] ?? [], JSON_UNESCAPED_UNICODE);
            $type['allowed_requesters'] = json_encode($type['allowed_requesters'], JSON_UNESCAPED_UNICODE);

            DB::table('document_type_catalogue')->updateOrInsert(
                ['code' => $type['code']],
                array_merge($type, ['updated_at' => $now, 'created_at' => $now]),
            );
        }
    }

    /**
     * Return the canonical document type catalogue.
     *
     * @return array<int, array<string, mixed>>
     */
    private function catalogue(): array
    {
        return [
            // 1 ── Proof of Enrolment ──────────────────────────────────────────
            [
                'code' => 'proof_of_enrolment',
                'label_ar' => 'شهادة قيد',
                'label_en' => 'Proof of Enrolment',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.student_code',
                    'student.academic_level',
                    'student.class_group_name',
                    'institution.name_ar',
                    'academic_year.name',
                    'semester.name',
                    'document.number',
                    'document.issued_at',
                ],
                'completeness_checks' => ['active_enrollment'],
                'approval_required' => false,
                'allowed_requesters' => ['guardian', 'staff'],
                'template_family' => 'enrolment',
                'validity_days' => 90,
                'public_verification' => true,
                'reissuable' => true,
                'display_order' => 10,
            ],

            // 2 ── School Acceptance Letter ────────────────────────────────────
            [
                'code' => 'school_acceptance_letter',
                'label_ar' => 'كتاب قبول مدرسي',
                'label_en' => 'School Acceptance Letter',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.birth_date',
                    'student.student_code',
                    'institution.name_ar',
                    'academic_year.name',
                    'document.number',
                    'document.issued_at',
                ],
                'completeness_checks' => ['active_enrollment'],
                'approval_required' => false,
                'allowed_requesters' => ['staff'],
                'template_family' => 'acceptance',
                'validity_days' => 30,
                'public_verification' => false,
                'reissuable' => true,
                'display_order' => 20,
            ],

            // 3 ── Semester Grade Report ───────────────────────────────────────
            [
                'code' => 'semester_grade_report',
                'label_ar' => 'كشف العلامات الفصلي',
                'label_en' => 'Semester Grade Report',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.student_code',
                    'student.class_group_name',
                    'student.academic_level',
                    'institution.name_ar',
                    'academic_year.name',
                    'semester.name',
                    'document.number',
                    'document.issued_at',
                ],
                'completeness_checks' => ['active_enrollment', 'marks_published'],
                'approval_required' => false,
                'allowed_requesters' => ['guardian', 'staff'],
                'template_family' => 'grade_report',
                'validity_days' => null,
                'public_verification' => false,
                'reissuable' => true,
                'display_order' => 30,
            ],

            // 4 ── Semester Attendance Report ─────────────────────────────────
            [
                'code' => 'semester_attendance_report',
                'label_ar' => 'كشف حضور وغياب فصلي',
                'label_en' => 'Semester Attendance Report',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.student_code',
                    'student.class_group_name',
                    'institution.name_ar',
                    'academic_year.name',
                    'semester.name',
                    'document.number',
                    'document.issued_at',
                ],
                'completeness_checks' => ['active_enrollment', 'attendance_published'],
                'approval_required' => false,
                'allowed_requesters' => ['guardian', 'staff'],
                'template_family' => 'attendance_report',
                'validity_days' => null,
                'public_verification' => false,
                'reissuable' => true,
                'display_order' => 40,
            ],

            // 5 ── Student Information Summary ─────────────────────────────────
            [
                'code' => 'student_information_summary',
                'label_ar' => 'ملخص بيانات الطالب',
                'label_en' => 'Student Information Summary',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.full_name_en',
                    'student.birth_date',
                    'student.student_code',
                    'guardian.full_name_ar',
                    'guardian.relationship_type',
                    'institution.name_ar',
                    'document.number',
                    'document.issued_at',
                ],
                'completeness_checks' => ['active_enrollment'],
                'approval_required' => true,
                'allowed_requesters' => ['staff'],
                'template_family' => 'information_summary',
                'validity_days' => 30,
                'public_verification' => false,
                'reissuable' => false,
                'display_order' => 50,
            ],

            // 6 ── Transfer Document ────────────────────────────────────────────
            [
                'code' => 'transfer_document',
                'label_ar' => 'وثيقة انتقال',
                'label_en' => 'Transfer Document',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.birth_date',
                    'student.student_code',
                    'student.academic_level',
                    'institution.name_ar',
                    'academic_year.name',
                    'document.number',
                    'document.issued_at',
                    'document.issued_by_name_ar',
                ],
                'completeness_checks' => ['active_enrollment'],
                'approval_required' => true,
                'allowed_requesters' => ['staff'],
                'template_family' => 'transfer',
                'validity_days' => 30,
                'public_verification' => true,
                'reissuable' => false,
                'display_order' => 60,
            ],

            // 7 ── End-of-Year Certificate ─────────────────────────────────────
            [
                'code' => 'end_of_year_certificate',
                'label_ar' => 'شهادة إتمام العام الدراسي',
                'label_en' => 'End-of-Year Certificate',
                'required_context_keys' => [
                    'student.full_name_ar',
                    'student.student_code',
                    'student.academic_level',
                    'institution.name_ar',
                    'academic_year.name',
                    'document.number',
                    'document.issued_at',
                    'document.issued_by_name_ar',
                ],
                'completeness_checks' => ['active_enrollment', 'marks_published', 'year_closed'],
                'approval_required' => true,
                'allowed_requesters' => ['staff'],
                'template_family' => 'certificate',
                'validity_days' => null,
                'public_verification' => true,
                'reissuable' => false,
                'display_order' => 70,
            ],
        ];
    }
}
