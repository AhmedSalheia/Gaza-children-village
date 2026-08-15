<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Import column mappings — maps a spreadsheet column header to an internal field.
 *
 * One row per column in the source file. `source_header` is the exact string
 * from the file; `internal_field` is the canonical domain field name.
 * `is_ignored` = true means the column is explicitly excluded from processing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_column_mappings', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('batch_id')->constrained('import_batches')->cascadeOnDelete();

            $table->string('source_header', 255);
            $table->string('internal_field', 128)->nullable();
            $table->boolean('is_ignored')->default(false);

            $table->index('batch_id');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_column_mappings');
    }
};
