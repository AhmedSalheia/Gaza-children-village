<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Versioned publication headers for student result snapshots.
 *
 * result_publications is the outer envelope:
 *   - scoped to institution_semester + optional class_group
 *   - versioned: each republish creates a new row (version N+1),
 *     old rows gain superseded_by_id pointing at the new one
 *   - immutable once published: corrections create a new version
 *   - revocable with permission + reason
 *   - institution_semester_id is a plain cross-module integer (no DB FK)
 *   - publisher_staff_profile_id is a plain cross-module integer (no DB FK)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('result_publications', function (Blueprint $table): void {
            $table->id();

            // Cross-module scope
            $table->unsignedBigInteger('institution_semester_id')->index();
            $table->unsignedBigInteger('class_group_id')->nullable()->index();

            // Versioning
            $table->unsignedSmallInteger('version')->default(1);
            $table->unsignedBigInteger('superseded_by_id')->nullable();

            // Lifecycle
            $table->string('status', 32)->default('published'); // published | revoked
            $table->dateTime('published_at');
            $table->unsignedBigInteger('publisher_staff_profile_id');

            // Revocation
            $table->dateTime('revoked_at')->nullable();
            $table->text('revoke_reason')->nullable();
            $table->unsignedBigInteger('revoked_by_staff_profile_id')->nullable();

            $table->timestamps();

            $table->index(['institution_semester_id', 'class_group_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('result_publications');
    }
};
