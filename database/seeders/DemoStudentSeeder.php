<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds 32 synthetic student profiles with varied lifecycle statuses.
 *
 * All names are synthetic and culturally appropriate for a Palestinian educational context.
 * No real personal data is used.
 *
 * Idempotent: checks student_code before inserting.
 */
final class DemoStudentSeeder extends Seeder
{
    /** @return array<int, array<string, mixed>> */
    private static function students(): array
    {
        return [
            // Active students (18)
            ['code' => 'STU-001', 'full_name_ar' => 'أحمد محمد علي حسن', 'full_name_en' => 'Ahmad Mohammad Ali Hassan', 'sex' => 'male', 'birth_date' => '2015-03-12', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-002', 'full_name_ar' => 'فاطمة عبد الله محمود', 'full_name_en' => 'Fatima Abdullah Mahmoud', 'sex' => 'female', 'birth_date' => '2016-07-22', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-003', 'full_name_ar' => 'محمد يوسف إبراهيم', 'full_name_en' => 'Mohammad Yousuf Ibrahim', 'sex' => 'male', 'birth_date' => '2014-11-05', 'orphan' => 'single_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-004', 'full_name_ar' => 'مريم حسن الخطيب', 'full_name_en' => 'Mariam Hassan Al-Khatib', 'sex' => 'female', 'birth_date' => '2015-01-30', 'orphan' => 'not_orphan', 'displacement' => 'externally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-005', 'full_name_ar' => 'عمر خالد النجار', 'full_name_en' => 'Omar Khaled Al-Najjar', 'sex' => 'male', 'birth_date' => '2016-04-18', 'orphan' => 'double_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-006', 'full_name_ar' => 'هالة سامي القاسم', 'full_name_en' => 'Hala Sami Al-Qasim', 'sex' => 'female', 'birth_date' => '2017-09-03', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-007', 'full_name_ar' => 'يوسف نبيل حمدان', 'full_name_en' => 'Yousuf Nabil Hamdan', 'sex' => 'male', 'birth_date' => '2015-06-14', 'orphan' => 'not_orphan', 'displacement' => 'returned', 'lifecycle' => 'active'],
            ['code' => 'STU-008', 'full_name_ar' => 'نور الدين علاء غزال', 'full_name_en' => 'Nour Al-Din Alaa Ghazal', 'sex' => 'male', 'birth_date' => '2014-02-28', 'orphan' => 'single_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-009', 'full_name_ar' => 'سلمى رامي البلبيسي', 'full_name_en' => 'Salma Rami Al-Balbisi', 'sex' => 'female', 'birth_date' => '2016-12-19', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-010', 'full_name_ar' => 'خالد وليد أبو سمرة', 'full_name_en' => 'Khaled Walid Abu Samra', 'sex' => 'male', 'birth_date' => '2015-08-07', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-011', 'full_name_ar' => 'دينا فراس العاصي', 'full_name_en' => 'Dina Firas Al-Aasi', 'sex' => 'female', 'birth_date' => '2017-03-25', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-012', 'full_name_ar' => 'إبراهيم كريم صالح', 'full_name_en' => 'Ibrahim Karim Saleh', 'sex' => 'male', 'birth_date' => '2014-10-11', 'orphan' => 'double_orphan', 'displacement' => 'externally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-013', 'full_name_ar' => 'رنا جمال العمري', 'full_name_en' => 'Rana Jamal Al-Omari', 'sex' => 'female', 'birth_date' => '2016-05-30', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-014', 'full_name_ar' => 'طارق حاتم الوحيدي', 'full_name_en' => 'Tariq Hatim Al-Wahidi', 'sex' => 'male', 'birth_date' => '2015-11-17', 'orphan' => 'single_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-015', 'full_name_ar' => 'ليلى حسين دياب', 'full_name_en' => 'Layla Hussain Diab', 'sex' => 'female', 'birth_date' => '2017-07-08', 'orphan' => 'not_orphan', 'displacement' => 'returned', 'lifecycle' => 'active'],
            ['code' => 'STU-016', 'full_name_ar' => 'آدم وائل الزغل', 'full_name_en' => 'Adam Wael Al-Zaghal', 'sex' => 'male', 'birth_date' => '2016-01-22', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],
            ['code' => 'STU-017', 'full_name_ar' => 'سارة باسم القدومي', 'full_name_en' => 'Sara Basim Al-Qadoumi', 'sex' => 'female', 'birth_date' => '2014-09-04', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'active'],
            ['code' => 'STU-018', 'full_name_ar' => 'كريم عصام الطيب', 'full_name_en' => 'Karim Issam Al-Tayyib', 'sex' => 'male', 'birth_date' => '2015-04-16', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'active'],

            // Inactive students (4)
            ['code' => 'STU-019', 'full_name_ar' => 'زياد أنور شحادة', 'full_name_en' => 'Ziad Anwar Shahadah', 'sex' => 'male', 'birth_date' => '2013-06-29', 'orphan' => 'not_orphan', 'displacement' => 'externally_displaced', 'lifecycle' => 'inactive'],
            ['code' => 'STU-020', 'full_name_ar' => 'هناء محمود القطان', 'full_name_en' => 'Hanaa Mahmoud Al-Qattan', 'sex' => 'female', 'birth_date' => '2014-02-14', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'inactive'],
            ['code' => 'STU-021', 'full_name_ar' => 'رامي جهاد طه', 'full_name_en' => 'Rami Jihad Taha', 'sex' => 'male', 'birth_date' => '2015-10-07', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'inactive'],
            ['code' => 'STU-022', 'full_name_ar' => 'أميرة فادي ناصر', 'full_name_en' => 'Amira Fadi Nasser', 'sex' => 'female', 'birth_date' => '2016-08-20', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'inactive'],

            // Withdrawn students (4)
            ['code' => 'STU-023', 'full_name_ar' => 'بلال سلطان الصايغ', 'full_name_en' => 'Bilal Sultan Al-Sayegh', 'sex' => 'male', 'birth_date' => '2013-12-03', 'orphan' => 'single_orphan', 'displacement' => 'externally_displaced', 'lifecycle' => 'withdrawn'],
            ['code' => 'STU-024', 'full_name_ar' => 'نادية رضا سليم', 'full_name_en' => 'Nadia Rida Salim', 'sex' => 'female', 'birth_date' => '2014-05-19', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'withdrawn'],
            ['code' => 'STU-025', 'full_name_ar' => 'علاء الدين ماجد حلس', 'full_name_en' => 'Alaa Al-Din Majid Halas', 'sex' => 'male', 'birth_date' => '2015-09-01', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'withdrawn'],
            ['code' => 'STU-026', 'full_name_ar' => 'وفاء علي البرغوثي', 'full_name_en' => 'Wafaa Ali Al-Barghouthi', 'sex' => 'female', 'birth_date' => '2016-03-27', 'orphan' => 'double_orphan', 'displacement' => 'externally_displaced', 'lifecycle' => 'withdrawn'],

            // Draft students (4) — no national ID; incomplete profiles
            ['code' => 'STU-027', 'full_name_ar' => 'جهاد صلاح الدين عودة', 'full_name_en' => null, 'sex' => 'male', 'birth_date' => '2016-06-11', 'orphan' => null, 'displacement' => null, 'lifecycle' => 'draft'],
            ['code' => 'STU-028', 'full_name_ar' => 'أسيل ناصر الخزندار', 'full_name_en' => null, 'sex' => 'female', 'birth_date' => '2017-02-05', 'orphan' => null, 'displacement' => null, 'lifecycle' => 'draft'],
            ['code' => 'STU-029', 'full_name_ar' => 'حمزة عدنان زيد', 'full_name_en' => null, 'sex' => 'male', 'birth_date' => null, 'orphan' => null, 'displacement' => null, 'lifecycle' => 'draft'],
            ['code' => 'STU-030', 'full_name_ar' => 'ياسمين حمدي مرتجى', 'full_name_en' => null, 'sex' => 'female', 'birth_date' => '2016-11-14', 'orphan' => null, 'displacement' => null, 'lifecycle' => 'draft'],

            // Graduated students (2)
            ['code' => 'STU-031', 'full_name_ar' => 'محمود فتحي السيد', 'full_name_en' => 'Mahmoud Fathi Al-Sayed', 'sex' => 'male', 'birth_date' => '2007-04-22', 'orphan' => 'not_orphan', 'displacement' => 'resident', 'lifecycle' => 'graduated'],
            ['code' => 'STU-032', 'full_name_ar' => 'إيمان كمال بركات', 'full_name_en' => 'Iman Kamal Barakat', 'sex' => 'female', 'birth_date' => '2007-08-15', 'orphan' => 'not_orphan', 'displacement' => 'internally_displaced', 'lifecycle' => 'graduated'],
        ];
    }

    public function run(): void
    {
        foreach (self::students() as $data) {
            if (DB::table('student_profiles')->where('student_code', $data['code'])->exists()) {
                continue;
            }

            // Create Person
            $personId = DB::table('people')->insertGetId([
                'full_name_ar' => $data['full_name_ar'],
                'full_name_en' => $data['full_name_en'],
                'birth_date' => $data['birth_date'],
                'birth_date_precision' => $data['birth_date'] !== null ? 'exact' : 'unknown',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create StudentProfile
            DB::table('student_profiles')->insert([
                'person_id' => $personId,
                'student_code' => $data['code'],
                'lifecycle_status' => $data['lifecycle'],
                'registered_on' => now()->subMonths(rand(1, 18))->toDateString(),
                'orphan_status' => $data['orphan'],
                'displacement_status' => $data['displacement'],
                'displacement_location' => $data['displacement'] === 'internally_displaced' ? 'Deir al-Balah' : null,
                'family_member_count' => rand(4, 10),
                'family_order' => rand(1, 5),
                'accessibility_indicator' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
