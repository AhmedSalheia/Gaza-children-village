<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * issued_documents
 *
 * Immutable record of every successfully generated PDF document.
 *
 * The storage_path is on the private disk — never a public URL.
 * The verification_code is a high-entropy random string (64 chars);
 * the verification_code_hash is its SHA-256 hash used for constant-time
 * lookup without exposing the plain code in slow queries.
 *
 * Cancellation records reason and timestamp without deleting the file
 * or the row (historical preservation requirement).
 *
 * Reissue: creates a new IssuedDocument; the superseded_by_id FK points
 * from the old document to the new one. The old document's cancelled_at
 * is set with cancellation_reason = 'superseded_by_reissue'.
 *
 * All cross-module IDs are plain integers with no DB foreign key constraints.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issued_documents', function (Blueprint $table): void {
            $table->id();

            // Human-readable unique document reference (GCV-TYPE-YEAR-SEQ)
            $table->string('document_number', 32)->unique();

            // What kind of document
            $table->string('document_type_code', 64)->index();

            // Cross-module plain int references — no DB FK
            $table->unsignedBigInteger('enrollment_id')->index();
            $table->unsignedBigInteger('student_profile_id')->index();
            $table->unsignedBigInteger('institution_id')->index();
            $table->unsignedBigInteger('institution_semester_id')->nullable()->index();

            // Which template version was used
            $table->foreignId('template_version_id')
                ->constrained('document_template_versions')
                ->restrictOnDelete();

            // Which request triggered issuance (nullable: staff may issue without portal request)
            $table->foreignId('request_id')
                ->nullable()
                ->constrained('student_document_requests')
                ->restrictOnDelete();

            // Document parameters
            $table->string('locale', 8)->default('ar');
            $table->unsignedBigInteger('approved_by_account_id')->nullable();
            $table->timestamp('issued_at')->useCurrent();

            // Verification
            $table->string('verification_code', 64)->unique();    // high-entropy plain code
            $table->string('verification_code_hash', 64)->unique(); // SHA-256(verification_code)

            // File storage
            $table->string('storage_path', 512);                  // path on private disk
            $table->string('file_sha256', 64);                    // SHA-256 of file content

            // Reissue chain
            $table->foreignId('supersedes_id')
                ->nullable()
                ->constrained('issued_documents')
                ->nullOnDelete();

            // Cancellation (preserves row and file)
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();

            $table->timestamps();

            // A request should produce at most one non-cancelled document
            // (uniqueness enforced at app layer for idempotency)
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('issued_documents');
    }
};
