<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `class_groups` table.
 *
 * A ClassGroup (section) is a cohort of students for a given academic level
 * within an InstitutionSemester and OperationalPeriod.
 *
 * institution_semester_id and operational_period_id are plain integers —
 * cross-module references to AcademicCalendar. institution is derived through
 * the InstitutionSemester chain; no redundant institution_id column here.
 *
 * academic_level_id and classroom_id are within-module FKs.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_groups', function (Blueprint $table): void {
            $table->id();
            // Cross-module plain integer references.
            $table->unsignedBigInteger('institution_semester_id');
            $table->unsignedBigInteger('operational_period_id');
            $table->foreignId('academic_level_id')->constrained('academic_levels')->restrictOnDelete();
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms')->nullOnDelete();
            $table->string('code', 32);
            $table->string('name_en')->nullable();
            $table->string('name_ar');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->string('lifecycle_status', 32)->default('draft');
            $table->timestamps();

            // Code is stable and unique within the institution semester.
            $table->unique(['institution_semester_id', 'code']);
            $table->index('institution_semester_id');
            $table->index('operational_period_id');
            $table->index('lifecycle_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_groups');
    }
};
