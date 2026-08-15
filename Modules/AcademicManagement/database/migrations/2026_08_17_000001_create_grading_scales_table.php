<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Institution-scoped grading scales used to interpret numeric marks.
 *
 * Each scale has a set of grade rows (grading_scale_grades) that define
 * the code/label, score range, pass/fail meaning, and display order.
 *
 * institution_id is a plain cross-module integer (no DB-level FK).
 * Scope: a scale belongs to one institution and may be assigned to mark sheets.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scales', function (Blueprint $table): void {
            $table->id();

            // Cross-module reference to institutions.id (no DB FK)
            $table->unsignedBigInteger('institution_id')->index();

            $table->string('code', 32);
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One code per institution
            $table->unique(['institution_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scales');
    }
};
