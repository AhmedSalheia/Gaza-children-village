<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `guardian_student_relationships` table.
 *
 * Records the legal/social relationship between a GuardianProfile and a
 * StudentProfile. Rows are NEVER deleted; when a relationship ends, ends_on
 * is set. A new row is created for any replacement relationship.
 *
 * Portal eligibility for a guardian to access a student requires:
 *   verification_status = 'verified' AND portal_eligible = true AND
 *   (ends_on IS NULL OR ends_on >= today)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student_relationships', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_profile_id')
                ->constrained('student_profiles')
                ->restrictOnDelete();
            $table->foreignId('guardian_profile_id')
                ->constrained('guardian_profiles')
                ->restrictOnDelete();
            $table->string('relationship_type', 32);
            $table->string('legal_authority', 32)->default('unknown');
            $table->string('verification_status', 32)->default('unverified');
            $table->boolean('portal_eligible')->default(false);
            $table->unsignedTinyInteger('contact_priority')->nullable();
            $table->boolean('is_emergency_contact')->default(false);
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->text('restricted_notes')->nullable();
            $table->string('evidence_status', 32)->nullable()->default('none');
            $table->string('evidence_reference', 255)->nullable();
            $table->json('history_metadata')->nullable();
            $table->timestamps();

            // Common lookup: all active relationships for a student
            $table->index(['student_profile_id', 'ends_on']);
            // Portal eligibility query
            $table->index(['guardian_profile_id', 'verification_status', 'portal_eligible'],'Portal_eligibility');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student_relationships');
    }
};
