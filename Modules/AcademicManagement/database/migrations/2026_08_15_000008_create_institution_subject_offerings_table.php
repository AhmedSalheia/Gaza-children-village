<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `institution_subject_offerings` table.
 *
 * Records which subjects are offered by an institution in a given semester.
 * institution_semester_id is a plain integer cross-module reference.
 * subject_id is a within-module FK.
 *
 * No teacher or classroom assignment here — that is a future teaching
 * assignments feature.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_subject_offerings', function (Blueprint $table): void {
            $table->id();
            // Cross-module plain integer reference.
            $table->unsignedBigInteger('institution_semester_id');
            $table->foreignId('subject_id')->constrained('subjects')->restrictOnDelete();
            $table->timestamps();

            // Each subject may be offered at most once per semester.
            $table->unique(['institution_semester_id', 'subject_id']);
            $table->index('institution_semester_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_subject_offerings');
    }
};
