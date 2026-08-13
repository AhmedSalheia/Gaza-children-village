<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Institution semesters activate a global semester for a specific institution.
 *
 * An institution semester is owned by AcademicCalendar and links an institution
 * (Organisation module) to a global semester (AcademicCalendar module). It holds
 * the institution-specific lifecycle and operational status, but never duplicates
 * academic-year facts from the global semester.
 *
 * Schema decisions:
 *   - status is a plain VARCHAR string; no DB ENUM.
 *   - No soft deletion; inactive records remain readable via archived status.
 *   - No actor-audit columns; F18 will add those through the audit engine.
 *   - copied_from_id is a nullable self-reference that preserves copy provenance;
 *     RESTRICT prevents deleting the source while the copy still references it.
 *   - institution_id + semester_id uniqueness is enforced at the DB level.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_semesters', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('semester_id');
            $table->string('status');
            $table->unsignedBigInteger('copied_from_id')->nullable();
            $table->timestamps();

            $table->unique(['institution_id', 'semester_id']);

            $table->foreign('institution_id')
                ->references('id')
                ->on('institutions')
                ->restrictOnDelete();

            $table->foreign('semester_id')
                ->references('id')
                ->on('semesters')
                ->restrictOnDelete();

            $table->foreign('copied_from_id')
                ->references('id')
                ->on('institution_semesters')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_semesters');
    }
};
