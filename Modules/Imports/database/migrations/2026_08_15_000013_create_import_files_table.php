<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import files — the stored file path for each batch.
 *
 * Files are stored in the application's private storage disk (never public).
 * The path is relative to the configured import storage disk root.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_files', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();

            // Storage path (relative to disk root, never a public URL).
            $table->string('storage_path', 512);

            // SHA-256 of file contents for integrity checking.
            $table->string('content_sha256', 64);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_files');
    }
};
