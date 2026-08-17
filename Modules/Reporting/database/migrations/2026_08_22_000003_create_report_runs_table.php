<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Report run log.
 *
 * Every time an actor runs a report (on-screen preview or export), a row is
 * inserted here. For background exports the operation_status_id column links
 * to the Notifications module's operation_statuses table for job tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('report_runs', function (Blueprint $table): void {
            $table->id();
            $table->string('definition_code');
            $table->foreign('definition_code')
                ->references('code')
                ->on('report_definitions')
                ->restrictOnDelete();

            $table->string('actor_type');       // 'admin' | 'staff'
            $table->unsignedBigInteger('actor_account_id');
            $table->string('portal');           // 'admin' | 'staff'
            $table->json('scope');              // filters applied (semester, class_group, dates…)
            $table->string('locale', 5)->default('ar');

            $table->enum('run_mode', ['preview', 'export'])->default('preview');
            $table->unsignedInteger('row_count')->nullable();
            $table->string('file_path')->nullable();        // set after sync export
            $table->unsignedBigInteger('operation_status_id')->nullable(); // set for async jobs
            $table->foreign('operation_status_id')
                ->references('id')
                ->on('operation_statuses')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_runs');
    }
};
