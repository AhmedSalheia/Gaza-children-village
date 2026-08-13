<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F07 — Academic year catalogue table.
 *
 * An academic year belongs to exactly one organization and represents one
 * named period of academic operations. Administrators define the dates;
 * no calendar is seeded automatically.
 *
 * Status lifecycle (enforced by application actions, not database):
 *   draft → open → closed → archived
 *   closed → open (reopen with reason)
 *   archived is terminal for ordinary actions
 *
 * Constraints:
 *   - code unique within organization (organization_id, code)
 *   - no soft deletion: deactivation and archiving are the lifecycle strategy
 *   - no database ENUM: status is a bounded string enforced in PHP
 *   - no actor-audit columns: deferred to F18/Audit module integration
 *   - foreign key to organizations is RESTRICT to prevent silent cascade-deletion
 *     of academic calendar configuration
 *
 * Date consistency (starts_on < ends_on) and business rules (one open year per
 * organization, semester containment) are enforced in application actions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')
                ->constrained('organizations')
                ->restrictOnDelete();
            $table->string('code');
            $table->string('name_en');
            $table->string('name_ar')->nullable();
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status');
            $table->timestamps();

            $table->unique(['organization_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
