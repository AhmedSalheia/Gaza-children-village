<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned immutable snapshots of verified attendance data.
 *
 * A snapshot is a point-in-time copy of verified attendance records for a
 * class group (and optionally a specific date range). The policy at publication
 * time is captured in detail_level / policy fields so the snapshot remains
 * self-describing if the policy later changes.
 *
 * Versioning, revocation, and supersession follow the same rules as
 * result_publications.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_publication_snapshots', function (Blueprint $table): void {
            $table->id();

            $table->unsignedBigInteger('institution_semester_id')->index();
            $table->unsignedBigInteger('class_group_id')->nullable()->index();

            // Date range covered by this snapshot (null = entire semester so far)
            $table->date('period_from')->nullable();
            $table->date('period_to')->nullable();

            // Versioning
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedBigInteger('superseded_by_id')->nullable();

            // Policy captured at publication time
            $table->string('detail_level', 32)->default('summary_only');
            $table->boolean('show_reason')->default(false);
            $table->boolean('show_arrival_departure')->default(false);

            // Lifecycle
            $table->string('status', 32)->default('published'); // published | revoked
            $table->dateTime('published_at');
            $table->unsignedBigInteger('publisher_staff_profile_id');

            // Revocation
            $table->dateTime('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->unsignedBigInteger('revoked_by_staff_profile_id')->nullable();

            $table->timestamps();

            $table->index(['institution_semester_id', 'class_group_id', 'status'],'ins_sem_cls_grp_stt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_publication_snapshots');
    }
};
