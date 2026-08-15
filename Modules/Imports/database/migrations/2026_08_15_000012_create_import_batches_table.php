<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import batches — one per upload session.
 *
 * A batch tracks the full lifecycle of one import file from upload through
 * parsed → mapped → validated → applied. It is owned by a staff actor and
 * scoped to one institution.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_batches', function (Blueprint $table): void {
            $table->id();

            $table->string('status', 32)->default('uploaded')->index();

            // Owning actor (staff account id) and institution context.
            $table->unsignedBigInteger('actor_account_id');
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('operational_period_id')->nullable();

            // File metadata (denormalised from import_files for quick access).
            $table->string('original_filename', 255);
            $table->string('mime_type', 64);
            $table->unsignedBigInteger('file_size_bytes');

            // Aggregate counts updated as rows are processed.
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('error_rows')->default(0);
            $table->unsignedInteger('applied_rows')->default(0);

            // Optional operator notes.
            $table->text('notes')->nullable();

            // Error captured if status transitions to 'failed'.
            $table->text('failure_message')->nullable();

            $table->timestamps();
            $table->timestamp('applied_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_batches');
    }
};
