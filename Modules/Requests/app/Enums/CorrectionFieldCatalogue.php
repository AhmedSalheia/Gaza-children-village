<?php

declare(strict_types=1);

namespace Modules\Requests\Enums;

/**
 * Governed catalogue of fields a guardian may request to correct.
 *
 * This is an exhaustive, code-owned list — guardians cannot request arbitrary
 * model/table/column changes. Any submission referencing a field_code not in
 * this catalogue is rejected at the service layer.
 *
 * Each case declares:
 *   classification()    — standard (secretary approvable) | sensitive (principal required)
 *   validationRules()   — Laravel validation rule array for the proposed_value string
 *   labelEn()           — English display label
 *   labelAr()           — Arabic display label
 *   requiresEncryption()— whether proposed value must be encrypted at rest
 *
 * Scope note:
 *   Address and DisplacementInfo are intentionally excluded; they require
 *   different storage and workflows outside the People module's contact/identifier
 *   append-only contracts.
 */
enum CorrectionFieldCatalogue: string
{
    /** Arabic full name as recorded in the civil registry */
    case StudentNameAr = 'student_name_ar';

    /** English / transliterated full name */
    case StudentNameEn = 'student_name_en';

    /** Date of birth — requires principal approval (sensitive) */
    case BirthDate = 'birth_date';

    /** Primary contact phone number — applied via People module CorrectContact/AddContact */
    case ContactPhone = 'contact_phone';

    /** Primary contact e-mail address — applied via People module CorrectContact/AddContact */
    case ContactEmail = 'contact_email';

    /** Guardian–student relationship type (e.g. mother, father, uncle) */
    case GuardianRelationshipType = 'guardian_relationship_type';

    /** Guardian legal authority status (e.g. legal_guardian, biological_parent) */
    case GuardianLegalAuthority = 'guardian_legal_authority';

    /** National/civil identifier correction — sensitive; applied via People module CorrectIdentifier */
    case IdentifierCorrection = 'identifier_correction';

    // -----------------------------------------------------------------
    // Properties
    // -----------------------------------------------------------------

    public function classification(): CorrectionClassification
    {
        return match ($this) {
            self::BirthDate,
            self::IdentifierCorrection => CorrectionClassification::Sensitive,
            default => CorrectionClassification::Standard,
        };
    }

    /** @return list<string> */
    public function validationRules(): array
    {
        return match ($this) {
            self::StudentNameAr => ['required', 'string', 'min:2', 'max:200', 'regex:/^[\p{Arabic}\s\-\.]+$/u'],
            self::StudentNameEn => ['required', 'string', 'min:2', 'max:200', 'regex:/^[A-Za-z\s\-\.\']+$/'],
            self::BirthDate => ['required', 'date', 'date_format:Y-m-d', 'before:today'],
            self::ContactPhone => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-\(\)]{7,20}$/'],
            self::ContactEmail => ['required', 'email:rfc', 'max:254'],
            self::GuardianRelationshipType => ['required', 'string', 'in:father,mother,grandfather,grandmother,uncle,aunt,sibling,legal_guardian,other'],
            self::GuardianLegalAuthority => ['required', 'string', 'in:biological_parent,legal_guardian,court_appointed,institutional_guardian'],
            self::IdentifierCorrection => ['required', 'string', 'min:5', 'max:50'],
        };
    }

    public function labelEn(): string
    {
        return match ($this) {
            self::StudentNameAr => 'Student Full Name (Arabic)',
            self::StudentNameEn => 'Student Full Name (English)',
            self::BirthDate => 'Date of Birth',
            self::ContactPhone => 'Contact Phone Number',
            self::ContactEmail => 'Contact E-mail Address',
            self::GuardianRelationshipType => 'Guardian Relationship Type',
            self::GuardianLegalAuthority => 'Guardian Legal Authority',
            self::IdentifierCorrection => 'Identity Document Number',
        };
    }

    public function labelAr(): string
    {
        return match ($this) {
            self::StudentNameAr => 'الاسم الكامل للطالب (عربي)',
            self::StudentNameEn => 'الاسم الكامل للطالب (إنجليزي)',
            self::BirthDate => 'تاريخ الميلاد',
            self::ContactPhone => 'رقم الهاتف',
            self::ContactEmail => 'البريد الإلكتروني',
            self::GuardianRelationshipType => 'نوع صلة القرابة',
            self::GuardianLegalAuthority => 'الصلاحية القانونية',
            self::IdentifierCorrection => 'رقم وثيقة الهوية',
        };
    }

    /**
     * Whether the proposed value for this field must be encrypted at rest.
     * Only applies to sensitive fields (birth_date, identifier_correction).
     */
    public function requiresEncryption(): bool
    {
        return $this->classification() === CorrectionClassification::Sensitive;
    }

    /** @return list<self> */
    public static function standardFields(): array
    {
        return array_filter(self::cases(), fn (self $c) => $c->classification() === CorrectionClassification::Standard);
    }

    /** @return list<self> */
    public static function sensitiveFields(): array
    {
        return array_filter(self::cases(), fn (self $c) => $c->classification() === CorrectionClassification::Sensitive);
    }
}
