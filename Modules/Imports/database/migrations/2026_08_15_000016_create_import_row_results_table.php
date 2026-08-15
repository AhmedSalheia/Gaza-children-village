<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import row results — one record per processed ImportRow.
 *
 * Created during ValidateRows and updated during ApplyImportBatch.
 * Holds the validation/apply outcome, any error detail, and the
 * proposed action (create / update / skip) for human review.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_row_results', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();
            $table->foreignId('row_id')->constrained('import_rows')->cascadeOnDelete();

            $table->string('status', 32)->index();

            // Human-readable summary (no raw national IDs).
            $table->string('summary', 512)->nullable();

            // Structured error detail for display (JSON). No raw IDs.
            $table->json('error_detail')->nullable();

            // Proposed domain action: 'create_student' | 'update_student' | 'skip'
            $table->string('proposed_action', 64)->nullable();

            // If an existing student was matched, record their ID for conflict display.
            $table->unsignedBigInteger('matched_student_id')->nullable();

            $table->index('batch_id');
            $table->index(['batch_id', 'status']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_row_results');
    }
};
