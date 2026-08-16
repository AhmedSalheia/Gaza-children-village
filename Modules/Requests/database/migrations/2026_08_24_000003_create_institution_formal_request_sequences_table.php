<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the institution_formal_request_sequences table.
 *
 * Tracks the per-institution, per-year sequential counter used to generate
 * stable, predictable request numbers (e.g. GCV-FR-2026-00001).
 *
 * Cross-module integer reference:
 *   institution_id → institutions.id  (plain int, no FK constraint)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_formal_request_sequences', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedSmallInteger('year');
            $table->unsignedInteger('current_sequence')->default(0);
            $table->timestamps();

            $table->unique(['institution_id', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_formal_request_sequences');
    }
};
