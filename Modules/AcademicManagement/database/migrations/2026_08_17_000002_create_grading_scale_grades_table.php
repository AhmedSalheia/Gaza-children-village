<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual grade tiers within a grading scale.
 *
 * Example rows for a typical scale:
 *   A+  Excellent  ممتاز   min=95  max=100  pass=true  seq=1
 *   A   Very Good  جيد جداً min=85 max=94.99 pass=true  seq=2
 *   D   Fail       راسب    min=0   max=49.99 pass=false seq=8
 *
 * Ranges are inclusive on both ends. Overlapping ranges within a scale
 * are rejected at the application layer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grading_scale_grades', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('grading_scale_id')
                ->constrained('grading_scales')
                ->cascadeOnDelete();

            $table->string('code', 16);       // e.g. "A+", "B", "D"
            $table->string('name_ar', 100);
            $table->string('name_en', 100)->nullable();
            $table->decimal('min_score', 7, 2);
            $table->decimal('max_score', 7, 2);
            $table->boolean('is_passing')->default(true);
            $table->unsignedTinyInteger('sequence')->default(0); // display order ASC
            $table->timestamps();

            $table->unique(['grading_scale_id', 'code']);
            $table->index(['grading_scale_id', 'sequence']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grading_scale_grades');
    }
};
