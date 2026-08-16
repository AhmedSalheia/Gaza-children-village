<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `secure_attachments` table.
 *
 * Security model:
 *   - `id` is a UUID generated server-side, never derived from user input.
 *   - `original_filename` is sanitized before storage (display only).
 *   - `storage_filename` is a UUID-based name, never user-supplied.
 *   - `storage_path` is stored server-side; callers cannot influence it.
 *   - `status` starts as 'quarantine' (pending virus scan) and transitions
 *     to 'available' or 'rejected' after scanning.
 *   - No updated_at — attachment rows are append-only. Status changes are
 *     written by a separate scanner job, not by user action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('secure_attachments', function (Blueprint $table): void {
            // UUID primary key — opaque to callers, server-generated
            $table->uuid('id')->primary();

            // Display filename — sanitized, shown in download prompt
            $table->string('original_filename', 255);

            // Storage filename — UUID-based, never derived from user input
            $table->string('storage_filename', 255)->unique();

            // MIME type detected by finfo, not supplied by the client
            $table->string('mime_type', 128);

            // File extension (lower-cased, from original filename, cross-checked with MIME)
            $table->string('extension', 16);

            // File size in bytes
            $table->unsignedBigInteger('size_bytes');

            // SHA-256 hex digest of file content — used for duplicate detection
            $table->string('sha256_hash', 64);

            // Storage disk name (matches config/filesystems.php key)
            $table->string('storage_disk', 64);

            // Path within the disk (e.g. 'institution-7/evidence/uuid.pdf')
            $table->string('storage_path', 512);

            // Uploader identity — derived from the authenticated session
            $table->string('uploader_actor_type', 32);
            $table->unsignedBigInteger('uploader_account_id');
            $table->string('uploader_portal', 32);

            // Institution scope — used for cross-institution access denial
            $table->unsignedBigInteger('institution_id');

            // Purpose / classification code (e.g. 'evidence')
            $table->string('classification', 64);

            // Lifecycle status
            // quarantine: uploaded, pending virus scan (or no scanner configured)
            // available:  scanned clean and ready to serve
            // rejected:   scanner detected a threat; file will be purged
            $table->string('status', 32)->default('quarantine');

            $table->timestamp('created_at')->useCurrent();

            // Indices
            $table->index('institution_id', 'sa_institution_id_idx');
            $table->index('sha256_hash', 'sa_sha256_idx');
            $table->index('uploader_account_id', 'sa_uploader_idx');
            $table->index('status', 'sa_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('secure_attachments');
    }
};
