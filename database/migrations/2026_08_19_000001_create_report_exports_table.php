<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Audit log for all report export events.
 *
 * Every time a staff or admin user exports a report to Excel/CSV the system
 * writes one row here BEFORE serving the download, providing an immutable
 * record of who downloaded what scope when.
 *
 * Columns are intentionally wide so any future report type can be recorded
 * without a schema change — the JSON scope column absorbs filter metadata.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_exports', function (Blueprint $table): void {
            $table->id();

            // Which kind of report was exported
            $table->string('export_type', 64);   // attendance_report | staff_attendance_report | marks_report | result_report

            // Actor identity — polymorphic-style without ORM poly overhead
            $table->string('actor_type', 32);    // admin | staff
            $table->unsignedBigInteger('actor_account_id');

            // Snapshot of the filters applied when exporting
            $table->json('scope');               // {institution_semester_id, class_group_id, date_from, date_to, ...}

            // Locale the file was requested in
            $table->string('locale', 8)->default('ar');

            // Row count in the exported file (null until generation completes)
            $table->unsignedInteger('row_count')->nullable();

            $table->timestamps();

            $table->index(['actor_type', 'actor_account_id']);
            $table->index('export_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_exports');
    }
};
