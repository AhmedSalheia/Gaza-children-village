<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Report definitions catalogue.
 *
 * Each row describes one report family: its code (primary key), human-readable
 * names, the permission key required to run it, and a JSON filter schema that
 * describes the filter controls to render in the UI.
 *
 * This table is reference/seed data only — rows are never created at runtime.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_definitions', function (Blueprint $table): void {
            $table->string('code')->primary();
            $table->string('name_ar');
            $table->string('name_en');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->string('report_family');   // logical grouping: registry, attendance, marks, …
            $table->json('filter_schema');     // describes which filter controls to display
            $table->string('permission_key');  // checked before allowing run or export
            $table->boolean('organization_scope_allowed')->default(false); // admin-only cross-institution view
            $table->boolean('admin_only')->default(false);                 // hidden in staff portal if true
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_definitions');
    }
};
