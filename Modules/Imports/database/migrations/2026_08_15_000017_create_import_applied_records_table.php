<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import applied records — audit trail of domain entities created/updated per row.
 *
 * One record per domain entity touched when an import row is applied.
 * A single import row may produce multiple records (e.g. Person + StudentProfile).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_applied_records', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('row_id')->constrained('import_rows')->cascadeOnDelete();
            $table->foreignId('result_id')->constrained('import_row_results')->cascadeOnDelete();

            // What type of entity was created/updated.
            // Values: 'person' | 'student_profile' | 'enrollment'
            $table->string('entity_type', 64);

            // The ID of the entity in its own module's table.
            $table->unsignedBigInteger('entity_id');

            // 'created' or 'updated'
            $table->string('operation', 16);

            $table->index('batch_id');
            $table->index(['entity_type', 'entity_id']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_applied_records');
    }
};
