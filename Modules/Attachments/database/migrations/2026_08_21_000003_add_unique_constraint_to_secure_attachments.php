<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique constraint on (institution_id, sha256_hash) to enforce
 * per-institution content deduplication at the database level.
 *
 * This is a safety-net for the application-level duplicate check in
 * SecureAttachmentService::store(). Without this constraint, concurrent
 * requests uploading identical content to the same institution could both
 * pass the pre-insert query and create duplicate rows. The constraint
 * ensures that even under race conditions, the database rejects the second
 * insert and the service recovers by returning the winning record.
 *
 * Design note: rejected-scan records share the same constraint space.
 * A file that was once rejected will always be rejected for that institution:
 * the pre-insert check detects the rejected row and surfaces a clear error
 * rather than re-storing known-bad content.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secure_attachments', function (Blueprint $table): void {
            $table->unique(
                ['institution_id', 'sha256_hash'],
                'sa_institution_sha256_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('secure_attachments', function (Blueprint $table): void {
            $table->dropUnique('sa_institution_sha256_unique');
        });
    }
};
