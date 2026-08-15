<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds synthetic import batches covering all RowResultStatus outcomes.
 *
 * Statuses demonstrated: created, updated, skipped_existing, conflict, invalid, failed.
 *
 * Idempotent: checks original_filename + actor_account_id combination.
 */
final class DemoImportBatchSeeder extends Seeder
{
    public function run(): void
    {
        $inst1Id = DB::table('institutions')->where('code', 'academy_1')->value('id');

        if ($inst1Id === null) {
            $this->command->warn('DemoImportBatchSeeder: academy_1 not found. Skipping.');

            return;
        }

        // Idempotency check: always use actor_account_id = 1 as the stable anchor for demo batches.
        $actorId = 1;

        $exists = DB::table('import_batches')
            ->where('original_filename', 'demo_import_completed.csv')
            ->exists();

        if ($exists) {
            return;
        }

        // Completed batch
        $batchId = DB::table('import_batches')->insertGetId([
            'status' => 'completed',
            'actor_account_id' => $actorId,
            'institution_id' => $inst1Id,
            'operational_period_id' => null,
            'original_filename' => 'demo_import_completed.csv',
            'mime_type' => 'text/csv',
            'file_size_bytes' => 4096,
            'total_rows' => 6,
            'valid_rows' => 4,
            'error_rows' => 2,
            'applied_rows' => 3,
            'notes' => 'Demo completed import batch',
            'failure_message' => null,
            'applied_at' => now()->subDays(3),
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(3),
        ]);

        // Import file record
        DB::table('import_files')->insert([
            'batch_id' => $batchId,
            'storage_path' => 'imports/demo/demo_import_completed.csv',
            'content_sha256' => str_repeat('a', 64), // placeholder for demo
            'created_at' => now()->subDays(5),
            'updated_at' => now()->subDays(5),
        ]);

        // Rows covering all result statuses
        $rows = [
            [
                'row_number' => 1,
                'raw_data' => json_encode(['الاسم' => 'أحمد سالم علي', 'تاريخ الميلاد' => '2015-03-12'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => 'أحمد سالم علي', 'birth_date' => '2015-03-12'], JSON_UNESCAPED_UNICODE),
                'status' => 'created',
                'summary' => 'New student: أحمد سالم علي',
                'proposed_action' => 'create_student',
                'error_detail' => null,
            ],
            [
                'row_number' => 2,
                'raw_data' => json_encode(['الاسم' => 'فاطمة أحمد محمد', 'تاريخ الميلاد' => '2016-07-22'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => 'فاطمة أحمد محمد', 'birth_date' => '2016-07-22'], JSON_UNESCAPED_UNICODE),
                'status' => 'updated',
                'summary' => 'Update existing: فاطمة أحمد محمد',
                'proposed_action' => 'update_student',
                'error_detail' => null,
            ],
            [
                'row_number' => 3,
                'raw_data' => json_encode(['الاسم' => 'محمد يوسف إبراهيم', 'تاريخ الميلاد' => '2014-11-05'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => 'محمد يوسف إبراهيم', 'birth_date' => '2014-11-05'], JSON_UNESCAPED_UNICODE),
                'status' => 'skipped_existing',
                'summary' => 'No change: محمد يوسف إبراهيم',
                'proposed_action' => 'skip',
                'error_detail' => null,
            ],
            [
                'row_number' => 4,
                'raw_data' => json_encode(['الاسم' => 'أحمد سالم علي', 'تاريخ الميلاد' => '2015-03-12'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => 'أحمد سالم علي', 'birth_date' => '2015-03-12'], JSON_UNESCAPED_UNICODE),
                'status' => 'conflict',
                'summary' => 'Duplicate national ID within this file — ambiguous identity, requires review.',
                'proposed_action' => 'skip',
                'error_detail' => json_encode(['duplicate_of_row' => 1]),
            ],
            [
                'row_number' => 5,
                'raw_data' => json_encode(['الاسم' => '', 'تاريخ الميلاد' => 'invalid-date'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => '', 'birth_date' => 'invalid-date'], JSON_UNESCAPED_UNICODE),
                'status' => 'invalid',
                'summary' => 'Row failed validation: full_name_ar is required; birth_date could not be parsed',
                'proposed_action' => 'skip',
                'error_detail' => json_encode(['errors' => ['full_name_ar is required', 'birth_date could not be parsed']]),
            ],
            [
                'row_number' => 6,
                'raw_data' => json_encode(['الاسم' => 'علاء الدين فريد'], JSON_UNESCAPED_UNICODE),
                'mapped_data' => json_encode(['full_name_ar' => 'علاء الدين فريد'], JSON_UNESCAPED_UNICODE),
                'status' => 'failed',
                'summary' => 'Apply error: Database constraint violation on save.',
                'proposed_action' => 'create_student',
                'error_detail' => json_encode(['exception' => 'SQLSTATE[23000] demo error']),
            ],
        ];

        foreach ($rows as $row) {
            $rowId = DB::table('import_rows')->insertGetId([
                'batch_id' => $batchId,
                'row_number' => $row['row_number'],
                'raw_data' => $row['raw_data'],
                'mapped_data' => $row['mapped_data'],
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ]);

            DB::table('import_row_results')->insert([
                'batch_id' => $batchId,
                'row_id' => $rowId,
                'status' => $row['status'],
                'summary' => $row['summary'],
                'error_detail' => $row['error_detail'],
                'proposed_action' => $row['proposed_action'],
                'matched_student_id' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ]);
        }

        // Applied records for created/updated rows
        foreach ([1, 2] as $rowNum) {
            $rowId = DB::table('import_rows')
                ->where('batch_id', $batchId)
                ->where('row_number', $rowNum)
                ->value('id');

            $resultId = DB::table('import_row_results')
                ->where('batch_id', $batchId)
                ->where('row_id', $rowId)
                ->value('id');

            if ($rowId && $resultId) {
                DB::table('import_applied_records')->insert([
                    'batch_id' => $batchId,
                    'row_id' => $rowId,
                    'result_id' => $resultId,
                    'entity_type' => 'student_profile',
                    'entity_id' => $rowNum,
                    'operation' => $rowNum === 1 ? 'created' : 'updated',
                    'created_at' => now()->subDays(3),
                    'updated_at' => now()->subDays(3),
                ]);
            }
        }
    }
}
