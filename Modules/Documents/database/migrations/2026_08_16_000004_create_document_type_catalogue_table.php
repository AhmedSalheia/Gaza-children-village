<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `document_type_catalogue` table.
 *
 * This is a code-governed reference table: rows are inserted by
 * DocumentTypeSeeder only and never mutated through the UI.
 *
 * Each row declares:
 *   - Arabic and English labels
 *   - Required data context fields (JSON array of dot-key names)
 *   - Data completeness checks that must pass before issuance
 *   - Whether principal approval is required before issuance
 *   - Who is allowed to request this document type (JSON array of 'guardian'|'staff'|'admin')
 *   - Template family name (groups variants of the same document)
 *   - Validity/expiry window in days (null = no expiry)
 *   - Whether the issued document is publicly verifiable
 *   - Whether re-issuance (after revocation) is permitted
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_catalogue', function (Blueprint $table): void {
            $table->string('code', 64)->primary();

            $table->string('label_ar', 255);
            $table->string('label_en', 255);

            // JSON array of DocumentDataContext dot-key paths required for this type.
            $table->json('required_context_keys');

            // JSON array of validation rule tags checked before issuance.
            // e.g. ['active_enrollment', 'marks_published', 'attendance_published']
            $table->json('completeness_checks')->nullable();

            $table->boolean('approval_required')->default(false);

            // JSON array: ['guardian', 'staff', 'admin']
            $table->json('allowed_requesters');

            // Groups related document types (e.g. 'grade_report' covers all semester variants)
            $table->string('template_family', 64)->nullable();

            // Validity window in days; null = document does not expire
            $table->unsignedSmallInteger('validity_days')->nullable();

            $table->boolean('public_verification')->default(false);

            $table->boolean('reissuable')->default(true);

            // Sort order for display
            $table->unsignedTinyInteger('display_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_catalogue');
    }
};
