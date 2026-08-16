<?php

declare(strict_types=1);

namespace Modules\Documents\Data;

/**
 * Typed value object that carries all data available for template rendering.
 *
 * Placeholder keys in templates (e.g. `{{ student.full_name_ar }}`) are resolved
 * from this context by TemplatePlaceholderResolver. The resolver only accepts
 * keys that are explicitly declared in its ALLOWED_PLACEHOLDERS catalogue, which
 * is derived from the public properties of this class.
 *
 * Constructor parameters use camelCase; they map to dot-notation placeholder keys
 * (e.g. `studentFullNameAr` → `student.full_name_ar`).
 *
 * Use `DocumentDataContext::synthetic()` to obtain a sample instance for
 * template preview rendering without touching real student records.
 */
final readonly class DocumentDataContext
{
    public function __construct(
        // student.*
        public string $studentFullNameAr,
        public string $studentFullNameEn,
        public string $studentBirthDate,
        public string $studentStudentCode,
        public string $studentAcademicLevel,
        public string $studentClassGroupName,

        // guardian.*
        public string $guardianFullNameAr,
        public string $guardianFullNameEn,
        public string $guardianRelationshipType,

        // institution.*
        public string $institutionNameAr,
        public string $institutionNameEn,
        public string $institutionCode,

        // organization.*
        public string $organizationNameAr,
        public string $organizationNameEn,

        // academic_year.*
        public string $academicYearName,

        // semester.*
        public string $semesterName,

        // document.*
        public string $documentNumber,
        public string $documentIssuedAt,
        public string $documentTypeLabelAr,
        public string $documentTypeLabelEn,
        public string $documentIssuedByNameAr,
    ) {}

    /**
     * Build a synthetic context for template preview rendering.
     *
     * All values are clearly fictional so no real student data is ever
     * included in a preview document.
     */
    public static function synthetic(string $documentTypeLabelAr = 'وثيقة نموذجية', string $documentTypeLabelEn = 'Sample Document'): self
    {
        return new self(
            studentFullNameAr: 'أحمد محمد السعيد',
            studentFullNameEn: 'Ahmad Mohammed Al-Saeed',
            studentBirthDate: '2012-09-15',
            studentStudentCode: 'STU-2025-00001',
            studentAcademicLevel: 'الصف الخامس',
            studentClassGroupName: '5-أ',
            guardianFullNameAr: 'محمد السعيد أحمد',
            guardianFullNameEn: 'Mohammed Al-Saeed Ahmed',
            guardianRelationshipType: 'الأب',
            institutionNameAr: 'مدرسة غزة النموذجية',
            institutionNameEn: 'Gaza Model School',
            institutionCode: 'GCV-DEMO-01',
            organizationNameAr: 'مجتمع غزة التطوعي',
            organizationNameEn: 'Gaza Community Volunteers',
            academicYearName: '2025–2026',
            semesterName: 'الفصل الأول',
            documentNumber: 'GCV-DEMO-2026-00001',
            documentIssuedAt: now()->format('Y-m-d'),
            documentTypeLabelAr: $documentTypeLabelAr,
            documentTypeLabelEn: $documentTypeLabelEn,
            documentIssuedByNameAr: 'المدير المدرسي',
        );
    }

    /**
     * Return a flat associative array mapping dot-notation placeholder keys
     * to their resolved string values.
     *
     * @return array<string, string>
     */
    public function toFlatArray(): array
    {
        return [
            'student.full_name_ar' => $this->studentFullNameAr,
            'student.full_name_en' => $this->studentFullNameEn,
            'student.birth_date' => $this->studentBirthDate,
            'student.student_code' => $this->studentStudentCode,
            'student.academic_level' => $this->studentAcademicLevel,
            'student.class_group_name' => $this->studentClassGroupName,
            'guardian.full_name_ar' => $this->guardianFullNameAr,
            'guardian.full_name_en' => $this->guardianFullNameEn,
            'guardian.relationship_type' => $this->guardianRelationshipType,
            'institution.name_ar' => $this->institutionNameAr,
            'institution.name_en' => $this->institutionNameEn,
            'institution.code' => $this->institutionCode,
            'organization.name_ar' => $this->organizationNameAr,
            'organization.name_en' => $this->organizationNameEn,
            'academic_year.name' => $this->academicYearName,
            'semester.name' => $this->semesterName,
            'document.number' => $this->documentNumber,
            'document.issued_at' => $this->documentIssuedAt,
            'document.type_label_ar' => $this->documentTypeLabelAr,
            'document.type_label_en' => $this->documentTypeLabelEn,
            'document.issued_by_name_ar' => $this->documentIssuedByNameAr,
        ];
    }
}
