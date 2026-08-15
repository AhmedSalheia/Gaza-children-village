<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * F06 — Institution-specific feature override table.
 *
 * Only meaningful override rows are permitted (enforced by application actions):
 *
 *   DefaultEnabled rule → is_enabled = false  (disable an otherwise-on feature)
 *   Allowed rule        → is_enabled = true   (enable an otherwise-off feature)
 *
 * Redundant, required, and unavailable overrides are rejected before reaching
 * this table. The constraint here is structural; the semantic contract lives in
 * SetInstitutionFeatureOverride.
 *
 * reason is nullable for F06. Management UI must not expose override mutation
 * until actor, permission, and Audit integration exist (F17+). At that point,
 * reason should be made required and every mutation audited with actor and
 * timestamp.
 *
 * No soft deletion: clearing an override restores type-derived behavior.
 * No actor-audit columns: deferred to Audit module integration (post-F17).
 * No database ENUM: is_enabled is a boolean column; rule semantics live in PHP.
 *
 * Foreign keys use RESTRICT to prevent silent cascade-deletion of configuration
 * when an institution or feature definition is deactivated. Deactivation must
 * preserve historical configuration for inspection.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_feature_overrides', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('institution_id')
                ->constrained('institutions')
                ->restrictOnDelete();
            $table->foreignId('feature_module_id')
                ->constrained('feature_modules')
                ->restrictOnDelete();
            $table->boolean('is_enabled');
            $table->string('reason')->nullable()->comment('Temporarily nullable; required once Audit/actor integration lands (post-F17).');
            $table->unique(['institution_id', 'feature_module_id'], 'institution_id_feature_module_id_unique');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_feature_overrides');
    }
};
