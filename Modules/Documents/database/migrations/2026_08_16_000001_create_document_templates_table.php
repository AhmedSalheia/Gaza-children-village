<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `document_templates` table.
 *
 * A document template is a family record for one document type at one
 * optional institution (or organization-wide when institution_id is null).
 *
 * `active_version_id` is a nullable FK to `document_template_versions`.
 * It is added after both tables exist via a deferred constraint in migration
 * 2026_08_16_000002.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table): void {
            $table->id();

            // Machine-readable document type code (FK to catalogue; no DB FK here —
            // the catalogue lives in a seeded reference table, not an ORM model table).
            $table->string('document_type_code', 64)->index();

            // Scope: null = organization-wide template; set = institution override.
            $table->unsignedBigInteger('organization_id')->nullable()->index();
            $table->unsignedBigInteger('institution_id')->nullable()->index();

            // Pointer to the currently live version; null until a version is activated.
            // The FK is added in migration 2026_08_16_000002 after the versions table exists.
            $table->unsignedBigInteger('active_version_id')->nullable();

            // Locale availability flags
            $table->boolean('ar_available')->default(true);
            $table->boolean('en_available')->default(false);

            // Institution-specific rendering overrides (logo URL, hex colour, etc.)
            $table->json('branding_config')->nullable();

            // Whether issuing a document of this type requires a principal approval.
            // Duplicates document_type_catalogue.approval_required at the template level
            // so each institution can tighten (but not loosen) the default.
            $table->boolean('approval_required')->default(false);

            $table->timestamps();

            // One template per (type, scope) combination.
            $table->unique(['document_type_code', 'organization_id', 'institution_id'], 'dt_type_scope_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_templates');
    }
};
