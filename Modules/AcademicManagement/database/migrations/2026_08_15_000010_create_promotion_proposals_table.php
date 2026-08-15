<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `promotion_proposals` table.
 *
 * A PromotionProposal is a staff-authored recommendation for what should happen
 * to a student after a semester ends (promoted, repeating, graduated, transferred,
 * or left unresolved). It requires an authorized review before it can be applied.
 *
 * source_enrollment_id and proposed_class_group_id are within-module FKs.
 * reviewed_by is a string actor reference (not a FK to any account table).
 *
 * Applying an approved proposal is atomic and irreversible; the source enrollment
 * is updated and a new enrollment is created if a new class group is proposed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotion_proposals', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('source_enrollment_id')
                ->constrained('student_enrollments')
                ->restrictOnDelete();
            $table->string('proposed_status', 32);
            $table->foreignId('proposed_class_group_id')
                ->nullable()
                ->constrained('class_groups')
                ->nullOnDelete();
            $table->string('review_status', 32)->default('pending');
            $table->string('reviewed_by', 255)->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->index('source_enrollment_id');
            $table->index('review_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotion_proposals');
    }
};
