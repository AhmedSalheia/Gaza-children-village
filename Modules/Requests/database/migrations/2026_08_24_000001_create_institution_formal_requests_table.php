<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the institution_formal_requests table.
 *
 * Each row is a single institution-to-management formal request backed by a
 * tracked 13-state workflow (current_status column).
 *
 * Cross-module integer references (no DB FK constraints):
 *   institution_id           → institutions.id  (Organization module)
 *   institution_semester_id  → institution_semesters.id  (AcademicCalendar)
 *   responsible_account_id   → staff_accounts.id  (Accounts module)
 *   created_by_account_id    → staff_accounts.id  (Accounts module)
 *   superseded_by_id         → self-reference (same table)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_formal_requests', function (Blueprint $table): void {
            $table->id();

            // Stable sequential number (e.g. GCV-FR-2026-00001), generated per institution+year
            $table->string('request_number', 32)->unique();

            // Cross-module references (plain integers, no FK)
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('institution_semester_id')->nullable();
            $table->unsignedBigInteger('responsible_account_id')->nullable();
            $table->unsignedBigInteger('created_by_account_id');

            // Request classification
            $table->string('request_type', 32);    // budget|staffing|maintenance|equipment|curriculum|administrative|other
            $table->string('title_ar', 500);
            $table->string('title_en', 500);
            $table->json('body');                   // structured content (sections/fields)
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('priority')->default(2); // 1=low 2=medium 3=high 4=urgent

            // Workflow state (directly managed, 13 possible states)
            $table->string('current_status', 40)->default('draft');

            // Revision tracking — increments when a returned request is edited and resubmitted
            $table->unsignedSmallInteger('version')->default(1);

            // Content hash at time of signing — set by the sign action; used by ElectronicApproval
            $table->string('content_hash', 64)->nullable();

            // Management response
            $table->json('response_body')->nullable();
            $table->timestamp('response_at')->nullable();

            // Supersession chain (when this request is replaced by a new version)
            $table->unsignedBigInteger('superseded_by_id')->nullable();

            $table->timestamps();
        });

        Schema::table('institution_formal_requests', function (Blueprint $table): void {
            $table->index('institution_id');
            $table->index('institution_semester_id');
            $table->index('current_status');
            $table->index(['institution_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_formal_requests');
    }
};
