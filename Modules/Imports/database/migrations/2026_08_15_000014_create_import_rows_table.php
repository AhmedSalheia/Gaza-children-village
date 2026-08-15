<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import rows — one record per data row in the source file.
 *
 * `raw_data` holds the original cell values keyed by spreadsheet column header.
 * `mapped_data` holds the normalized values keyed by internal field name,
 * populated after column mapping is applied.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_rows', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();

            // 1-based row number within the source file (header = row 0).
            $table->unsignedInteger('row_number');

            // Raw cell values as parsed from the file (JSON object).
            $table->json('raw_data');

            // Mapped values after column-alias translation (JSON object or null).
            $table->json('mapped_data')->nullable();

            $table->index('batch_id');
            $table->index(['batch_id', 'row_number']);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_rows');
    }
};
