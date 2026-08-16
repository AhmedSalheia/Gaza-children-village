<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds institution_semester_id to student_document_requests so that
 * staff scope filters (institution + semester) can be applied without
 * requiring a join through student_enrollments on every read.
 *
 * Also adds a unique index on issued_documents.request_id to enforce
 * at the database level that only one active (non-cancelled) document
 * can exist per request. Because partial indexes are not portable, we
 * enforce "one document total per request" at the schema level and rely
 * on the application to cancel before reissuing.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Add institution_semester_id to requests
        Schema::table('student_document_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('institution_semester_id')->nullable()->after('institution_id')->index();
        });

        // Unique: one issued_documents row per request (application ensures
        // old documents are cancelled before a reissue creates a new row)
        Schema::table('issued_documents', function (Blueprint $table): void {
            $table->unique('request_id', 'issued_documents_request_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('issued_documents', function (Blueprint $table): void {
            $table->dropUnique('issued_documents_request_id_unique');
        });

        Schema::table('student_document_requests', function (Blueprint $table): void {
            $table->dropColumn('institution_semester_id');
        });
    }
};
