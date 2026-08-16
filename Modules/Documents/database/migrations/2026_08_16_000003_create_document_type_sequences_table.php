<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `document_type_sequences` table.
 *
 * Each row maintains the current sequential counter for one
 * (type_code, institution_id, year) combination.
 *
 * DocumentNumberService::next() acquires a `lockForUpdate` on the matching row
 * inside a DB transaction, increments `current_sequence`, and returns the new
 * number — guaranteeing no two concurrent calls can receive the same value.
 *
 * Rows are inserted lazily on first use by the service; `INSERT OR IGNORE`
 * semantics handle the race on row creation.
 *
 * Generated number format: GCV-{TYPE_ABBREV}-{YEAR}-{SEQ padded to 5 digits}
 * Example: GCV-POE-2026-00001
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_type_sequences', function (Blueprint $table): void {
            $table->id();

            $table->string('type_code', 64)->index();

            // Cross-module plain integer FK to institutions (no DB FK constraint —
            // institutions are in a separate module).
            $table->unsignedBigInteger('institution_id')->index();

            $table->unsignedSmallInteger('year');

            // Starts at 0; DocumentNumberService increments before returning.
            $table->unsignedInteger('current_sequence')->default(0);

            $table->timestamps();

            $table->unique(['type_code', 'institution_id', 'year'], 'dts_type_inst_year_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_sequences');
    }
};
