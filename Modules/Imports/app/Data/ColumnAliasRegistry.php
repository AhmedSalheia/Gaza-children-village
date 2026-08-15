<?php

declare(strict_types=1);

namespace Modules\Imports\Data;

/**
 * Maps Arabic and English column header aliases to internal field names.
 *
 * When a user uploads a spreadsheet, the column headers may be in Arabic,
 * English, or mixed. This registry provides the canonical internal field
 * name for each known alias so that MapColumns can auto-suggest mappings.
 *
 * All comparisons are case-insensitive and trim whitespace.
 *
 * Internal field names:
 *   full_name_ar     — Arabic full name (required)
 *   full_name_en     — English full name (optional)
 *   birth_date       — ISO date or localized date string
 *   national_id      — Palestinian national ID (9 digits; treated as sensitive)
 *   gender           — male / female / ذكر / أنثى
 *   orphan_status    — orphan classification
 *   displacement_status — displacement state
 *   displacement_location — free-text location
 *   family_member_count  — integer
 *   family_order         — integer
 *   accessibility_indicator — boolean
 *   registered_on        — registration date
 *   class_group_code — target class group for enrollment
 */
final class ColumnAliasRegistry
{
    /**
     * Alias → internal field name mapping.
     * Keys are lowercase-trimmed alias strings.
     *
     * @var array<string, string>
     */
    private static array $aliases = [
        // full_name_ar
        'الاسم' => 'full_name_ar',
        'الاسم الكامل' => 'full_name_ar',
        'اسم الطالب' => 'full_name_ar',
        'الاسم بالعربي' => 'full_name_ar',
        'اسم كامل' => 'full_name_ar',
        'الاسم الرباعي' => 'full_name_ar',
        'full_name_ar' => 'full_name_ar',
        'name_ar' => 'full_name_ar',
        'arabic name' => 'full_name_ar',
        'arabic_name' => 'full_name_ar',
        'full name (arabic)' => 'full_name_ar',

        // full_name_en
        'full_name_en' => 'full_name_en',
        'name_en' => 'full_name_en',
        'english name' => 'full_name_en',
        'english_name' => 'full_name_en',
        'الاسم بالإنجليزي' => 'full_name_en',
        'الاسم الإنجليزي' => 'full_name_en',
        'full name (english)' => 'full_name_en',

        // birth_date
        'تاريخ الميلاد' => 'birth_date',
        'birth_date' => 'birth_date',
        'birthdate' => 'birth_date',
        'date of birth' => 'birth_date',
        'birth date' => 'birth_date',
        'dob' => 'birth_date',
        'born' => 'birth_date',

        // national_id
        'الرقم الوطني' => 'national_id',
        'رقم الهوية' => 'national_id',
        'رقم الهوية الوطنية' => 'national_id',
        'هوية' => 'national_id',
        'national_id' => 'national_id',
        'national id' => 'national_id',
        'id number' => 'national_id',
        'id_number' => 'national_id',
        'id' => 'national_id',

        // gender
        'الجنس' => 'gender',
        'gender' => 'gender',
        'sex' => 'gender',

        // orphan_status
        'وضع اليتم' => 'orphan_status',
        'يتيم' => 'orphan_status',
        'orphan_status' => 'orphan_status',
        'orphan' => 'orphan_status',
        'orphan status' => 'orphan_status',

        // displacement_status
        'وضع النزوح' => 'displacement_status',
        'نازح' => 'displacement_status',
        'displacement_status' => 'displacement_status',
        'displacement' => 'displacement_status',
        'displacement status' => 'displacement_status',

        // displacement_location
        'موقع النزوح' => 'displacement_location',
        'مكان النزوح' => 'displacement_location',
        'displacement_location' => 'displacement_location',
        'displacement location' => 'displacement_location',

        // family_member_count
        'عدد أفراد الأسرة' => 'family_member_count',
        'أفراد الأسرة' => 'family_member_count',
        'family_member_count' => 'family_member_count',
        'family members' => 'family_member_count',
        'family size' => 'family_member_count',

        // family_order
        'ترتيب في الأسرة' => 'family_order',
        'الترتيب في الأسرة' => 'family_order',
        'family_order' => 'family_order',
        'family order' => 'family_order',
        'birth order' => 'family_order',

        // accessibility_indicator
        'ذوي الاحتياجات الخاصة' => 'accessibility_indicator',
        'إعاقة' => 'accessibility_indicator',
        'accessibility_indicator' => 'accessibility_indicator',
        'accessibility' => 'accessibility_indicator',
        'special needs' => 'accessibility_indicator',
        'disability' => 'accessibility_indicator',

        // registered_on
        'تاريخ التسجيل' => 'registered_on',
        'registered_on' => 'registered_on',
        'registration date' => 'registered_on',
        'enrolled on' => 'registered_on',

        // class_group_code
        'رمز الفصل' => 'class_group_code',
        'الفصل' => 'class_group_code',
        'class_group_code' => 'class_group_code',
        'class group' => 'class_group_code',
        'class' => 'class_group_code',
        'group code' => 'class_group_code',
    ];

    /**
     * Resolve a spreadsheet column header to an internal field name.
     * Returns null if the alias is not recognized.
     */
    public static function resolve(string $header): ?string
    {
        $key = mb_strtolower(trim($header));

        return self::$aliases[$key] ?? null;
    }

    /**
     * Return all known internal field names.
     *
     * @return list<string>
     */
    public static function internalFields(): array
    {
        return array_unique(array_values(self::$aliases));
    }

    /**
     * Return all known aliases for a given internal field name.
     *
     * @return list<string>
     */
    public static function aliasesFor(string $internalField): array
    {
        return array_keys(array_filter(self::$aliases, fn ($v) => $v === $internalField));
    }
}
