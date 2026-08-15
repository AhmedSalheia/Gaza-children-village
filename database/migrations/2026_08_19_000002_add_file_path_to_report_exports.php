<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds file_path to report_exports so the download controller can look up
 * the stored file by export record, verify actor ownership, and serve it —
 * rather than trusting a user-supplied encrypted path.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            // Relative path under storage/app/, e.g. reports/<uuid>/filename.xlsx
            $table->string('file_path', 512)->nullable()->after('row_count');
        });
    }

    public function down(): void
    {
        Schema::table('report_exports', function (Blueprint $table): void {
            $table->dropColumn('file_path');
        });
    }
};
