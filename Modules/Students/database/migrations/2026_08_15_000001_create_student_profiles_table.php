<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `student_profiles` table.
 *
 * StudentProfile extends Person with student-specific operational and welfare
 * data. A Person may have at most one StudentProfile (unique index on person_id).
 *
 * person_id is a cross-module FK to people.id; stored as a constrained FK
 * following the same pattern used by staff_profiles.
 *
 * Welfare fields (orphan_status, displacement_status, etc.) require the
 * student.view_restricted permission to read — enforced in the action layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('student_code', 32)->unique();
            $table->string('lifecycle_status', 32)->default('draft');
            $table->date('registered_on');

            // Welfare and social fields — require student.view_restricted permission
            $table->string('orphan_status', 32)->nullable();
            $table->string('displacement_status', 32)->nullable();
            $table->string('displacement_location', 255)->nullable();
            $table->unsignedSmallInteger('family_member_count')->nullable();
            $table->unsignedTinyInteger('family_order')->nullable();
            $table->boolean('accessibility_indicator')->default(false);

            $table->timestamps();

            $table->index('lifecycle_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
