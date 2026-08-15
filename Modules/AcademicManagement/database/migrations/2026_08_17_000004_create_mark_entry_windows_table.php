<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mark-entry windows control the period during which teachers may enter marks.
 *
 * A window can be institution-semester-wide or restricted to specific
 * class groups and/or subject offerings.
 *
 * Status lifecycle:
 *   scheduled → open → closed
 *                   → extended (new closes_at set; still open)
 *                   → cancelled
 *
 * Extension history is stored as a JSON array so principals can audit
 * all extensions without a separate table.
 *
 * institution_semester_id is a plain cross-module integer (no DB FK).
 * class_group_id and subject_offering_id are nullable within-module FKs.
 * created_by_staff_position_id is a plain cross-module integer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mark_entry_windows', function (Blueprint $table): void {
            $table->id();

            // Cross-module reference to institution_semesters.id
            $table->unsignedBigInteger('institution_semester_id')->index();

            // Optional scope restriction — null means applies to all groups/subjects
            $table->foreignId('class_group_id')
                ->nullable()
                ->constrained('class_groups')
                ->nullOnDelete();

            $table->foreignId('subject_offering_id')
                ->nullable()
                ->constrained('institution_subject_offerings')
                ->nullOnDelete();

            $table->string('name_ar', 150)->nullable();
            $table->string('name_en', 150)->nullable();

            $table->dateTime('opens_at');
            $table->dateTime('closes_at');

            // scheduled | open | closed | extended | cancelled
            $table->string('status', 32)->default('scheduled');

            // JSON: [{extended_at, new_closes_at, reason, actor_ref}]
            $table->json('extension_history')->nullable();

            // Who created this window (cross-module staff position, no DB FK)
            $table->unsignedBigInteger('created_by_staff_position_id')->nullable();

            $table->timestamps();

            $table->index(['institution_semester_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mark_entry_windows');
    }
};
