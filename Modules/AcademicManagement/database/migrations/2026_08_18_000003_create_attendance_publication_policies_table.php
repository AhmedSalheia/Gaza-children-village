<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-institution-semester configuration for guardian-visible attendance data.
 *
 * Defaults: disabled, reasons hidden, arrival/departure hidden, summary_only.
 * One row per institution semester (unique constraint enforced).
 * institution_semester_id is a plain cross-module integer (no DB FK).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_publication_policies', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('institution_semester_id')->unique();

            // Master switch — no snapshots can be created while disabled
            $table->boolean('enabled')->default(false);

            // What level of detail guardians see
            // summary_only: total present/absent/late counts per student
            // daily_status: individual date-level status codes (no times or reasons unless explicitly enabled)
            $table->string('detail_level', 32)->default('summary_only');

            // Delay (in days) after which an attendance day becomes visible.
            // 0 means visible as soon as snapshot is published.
            $table->unsignedTinyInteger('publish_delay_days')->default(0);

            // Whether the reason field is included in snapshots
            $table->boolean('show_reason')->default(false);

            // Whether arrival/departure times are included in snapshots
            $table->boolean('show_arrival_departure')->default(false);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_publication_policies');
    }
};
