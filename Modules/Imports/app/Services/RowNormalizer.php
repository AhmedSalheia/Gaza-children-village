<?php

declare(strict_types=1);

namespace Modules\Imports\Services;

/**
 * Normalizes mapped row data into typed domain values.
 *
 * Takes a `mapped_data` array (keyed by internal field name) and
 * returns a normalized array suitable for domain action arguments.
 *
 * All normalization is lossless — original values are not discarded.
 * Invalid values produce an error entry rather than a silent default.
 *
 * National ID handling: if `national_id` is present it is passed through
 * to the normalizer for validation; the raw value is never stored in error
 * messages (use masked form only).
 */
final class RowNormalizer
{
    /**
     * Normalize a mapped-data row.
     *
     * @param  array<string, scalar|null>  $mappedData
     * @return array{data: array<string, mixed>, errors: list<string>}
     */
    public function normalize(array $mappedData): array
    {
        $data = [];
        $errors = [];

        // full_name_ar — required
        $nameAr = trim((string) ($mappedData['full_name_ar'] ?? ''));
        if ($nameAr === '') {
            $errors[] = 'full_name_ar is required';
        } else {
            $data['full_name_ar'] = $nameAr;
        }

        // full_name_en — optional
        $nameEn = trim((string) ($mappedData['full_name_en'] ?? ''));
        $data['full_name_en'] = $nameEn !== '' ? $nameEn : null;

        // birth_date — optional, parse to Y-m-d
        $birthDateRaw = trim((string) ($mappedData['birth_date'] ?? ''));
        if ($birthDateRaw !== '') {
            $parsed = $this->parseDate($birthDateRaw);
            if ($parsed === null) {
                $errors[] = 'birth_date could not be parsed';
            } else {
                $data['birth_date'] = $parsed;
            }
        } else {
            $data['birth_date'] = null;
        }

        // national_id — sensitive; validated without storing raw value in errors
        $rawId = trim((string) ($mappedData['national_id'] ?? ''));
        if ($rawId !== '') {
            $normalizerClass = 'Modules\\People\\Services\\PalestinianIdNormalizer';
            $normalizer = new $normalizerClass;
            if ($normalizer->isValid($rawId)) {
                $data['national_id_raw'] = $rawId; // only kept internally, never in error messages
            } else {
                $errors[] = 'national_id format is invalid';
            }
        } else {
            $data['national_id_raw'] = null;
        }

        // gender
        $gender = $this->normalizeGender(trim((string) ($mappedData['gender'] ?? '')));
        $data['gender'] = $gender;

        // orphan_status
        $data['orphan_status'] = trim((string) ($mappedData['orphan_status'] ?? '')) ?: null;

        // displacement_status
        $data['displacement_status'] = trim((string) ($mappedData['displacement_status'] ?? '')) ?: null;

        // displacement_location
        $data['displacement_location'] = trim((string) ($mappedData['displacement_location'] ?? '')) ?: null;

        // family_member_count
        $fmc = trim((string) ($mappedData['family_member_count'] ?? ''));
        if ($fmc !== '') {
            if (ctype_digit($fmc) && (int) $fmc >= 1) {
                $data['family_member_count'] = (int) $fmc;
            } else {
                $errors[] = 'family_member_count must be a positive integer';
            }
        } else {
            $data['family_member_count'] = null;
        }

        // family_order
        $fo = trim((string) ($mappedData['family_order'] ?? ''));
        if ($fo !== '') {
            if (ctype_digit($fo) && (int) $fo >= 1) {
                $data['family_order'] = (int) $fo;
            } else {
                $errors[] = 'family_order must be a positive integer';
            }
        } else {
            $data['family_order'] = null;
        }

        // accessibility_indicator — accepts true/yes/1/نعم
        $acc = trim((string) ($mappedData['accessibility_indicator'] ?? ''));
        $data['accessibility_indicator'] = $acc !== '' ? $this->normalizeBool($acc) : null;

        // registered_on
        $regRaw = trim((string) ($mappedData['registered_on'] ?? ''));
        if ($regRaw !== '') {
            $parsed = $this->parseDate($regRaw);
            if ($parsed === null) {
                $errors[] = 'registered_on could not be parsed';
            } else {
                $data['registered_on'] = $parsed;
            }
        } else {
            $data['registered_on'] = null;
        }

        // class_group_code
        $data['class_group_code'] = trim((string) ($mappedData['class_group_code'] ?? '')) ?: null;

        return ['data' => $data, 'errors' => $errors];
    }

    private function parseDate(string $raw): ?string
    {
        // Try ISO 8601 first (Y-m-d).
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $raw)) {
            $ts = strtotime($raw);

            return $ts !== false ? date('Y-m-d', $ts) : null;
        }

        // Try d/m/Y and d-m-Y.
        foreach (['d/m/Y', 'd-m-Y', 'm/d/Y', 'Y/m/d'] as $format) {
            $dt = \DateTime::createFromFormat($format, $raw);
            if ($dt !== false) {
                return $dt->format('Y-m-d');
            }
        }

        return null;
    }

    private function normalizeGender(string $raw): ?string
    {
        return match (mb_strtolower($raw)) {
            'male', 'm', 'ذكر', 'ولد' => 'male',
            'female', 'f', 'أنثى', 'بنت', 'girl' => 'female',
            default => $raw !== '' ? $raw : null,
        };
    }

    private function normalizeBool(string $raw): bool
    {
        return in_array(mb_strtolower($raw), ['true', 'yes', '1', 'نعم', 'صح'], true);
    }
}
