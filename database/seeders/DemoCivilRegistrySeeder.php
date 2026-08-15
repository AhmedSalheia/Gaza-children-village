<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds synthetic Gaza civil registry records.
 *
 * Scenarios covered:
 * - Records that will match registered students (via lookup_fingerprint)
 * - Records with no corresponding student (orphan CR entries)
 * - A record flagged as deceased
 * - A record with mismatched name data (simulates data drift)
 *
 * IMPORTANT: national IDs are synthetic 9-digit numbers that do not
 * correspond to any real person. lookup_fingerprint is SHA-256 of a
 * demo HMAC key + national ID, using a stable demo key.
 *
 * Idempotent: checks lookup_fingerprint before inserting.
 */
final class DemoCivilRegistrySeeder extends Seeder
{
    /** Demo HMAC key used only for seeding. Not a production key. */
    private const DEMO_CR_KEY = 'demo-civil-registry-hmac-key-2026';

    public function run(): void
    {
        $records = [
            // STU-001 has a matching CR record (name matches)
            [
                'national_id' => '100000001',
                'full_name' => 'أحمد محمد علي حسن',
                'gender' => 'male',
                'area' => 'Deir al-Balah',
                'city' => 'Gaza',
                'birth_date' => '2015-03-12',
                'is_deceased' => false,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
            // STU-002 has a CR record — name matches
            [
                'national_id' => '100000002',
                'full_name' => 'فاطمة عبد الله محمود',
                'gender' => 'female',
                'area' => 'Khan Younis',
                'city' => 'Gaza',
                'birth_date' => '2016-07-22',
                'is_deceased' => false,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
            // Deceased flag case — not linked to any student
            [
                'national_id' => '100000010',
                'full_name' => 'ناصر حسن الدهدوه',
                'gender' => 'male',
                'area' => 'Jabalia',
                'city' => 'North Gaza',
                'birth_date' => '2012-05-08',
                'is_deceased' => true,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
            // Mismatched name — national ID exists but name in CR differs from student record
            [
                'national_id' => '100000003',
                'full_name' => 'محمد يوسف أحمد إبراهيم',  // slight name drift
                'gender' => 'male',
                'area' => 'Rafah',
                'city' => 'Gaza',
                'birth_date' => '2014-11-05',
                'is_deceased' => false,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
            // Orphan CR entries (no corresponding GCV student)
            [
                'national_id' => '100000020',
                'full_name' => 'صالح عبد الفتاح الجمل',
                'gender' => 'male',
                'area' => 'Beit Lahia',
                'city' => 'North Gaza',
                'birth_date' => '2014-09-18',
                'is_deceased' => false,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
            [
                'national_id' => '100000021',
                'full_name' => 'عائشة يحيى حمادة',
                'gender' => 'female',
                'area' => 'Beit Lahia',
                'city' => 'North Gaza',
                'birth_date' => '2015-06-03',
                'is_deceased' => false,
                'marital_status' => 'single',
                'religion' => 'Islam',
                'birth_country' => 'Palestine',
            ],
        ];

        foreach ($records as $record) {
            $fingerprint = hash_hmac('sha256', $record['national_id'], self::DEMO_CR_KEY);

            if (DB::table('gaza_civil_records')->where('lookup_fingerprint', $fingerprint)->exists()) {
                continue;
            }

            DB::table('gaza_civil_records')->insert([
                'lookup_fingerprint' => $fingerprint,
                'full_name' => $record['full_name'],
                'gender' => $record['gender'],
                'area' => $record['area'],
                'city' => $record['city'],
                'street' => null,
                'birth_date' => $record['birth_date'],
                'marital_status' => $record['marital_status'],
                'is_deceased' => $record['is_deceased'],
                'religion' => $record['religion'],
                'birth_country' => $record['birth_country'],
                'father_id_correlation' => null,
                'mother_id_correlation' => null,
                'representative_id_correlation' => null,
                'representative_relationship' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
