<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `document_template_versions` table and adds the deferred
 * `active_version_id` FK back onto `document_templates`.
 *
 * Immutability is enforced at the application layer by
 * DocumentTemplateVersionService; the schema does not use DB triggers.
 *
 * `body` stores the raw UTF-8 HTML template with `{{ dot.key }}` placeholders.
 * No PHP, Blade directives, or JS may be stored here.
 *
 * `content_hash` is a SHA-256 of the canonical body (trimmed, NFC-normalized).
 * It is recomputed on activation and stored for integrity verification.
 *
 * `status` — 'draft' | 'active' | 'archived'
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_template_versions', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('template_id')
                ->constrained('document_templates')
                ->cascadeOnDelete();

            // Monotonically increasing within the template; set once on creation.
            $table->unsignedSmallInteger('version_number');

            // 'ar' | 'en'
            $table->string('locale', 8)->default('ar');

            // Raw HTML with approved {{ dot.key }} placeholders only.
            $table->mediumText('body');

            // JSON array of placeholder keys found in body (for display + validation).
            $table->json('placeholder_catalogue')->nullable();

            // Optional page-level header/footer: { "html": "...", "height_mm": 15 }
            $table->json('header_config')->nullable();
            $table->json('footer_config')->nullable();

            // Validity window (nullable — null means no constraint)
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();

            // draft | active | archived
            $table->string('status', 16)->default('draft')->index();

            // Audit trail
            $table->unsignedBigInteger('creator_account_id')->nullable();
            $table->unsignedBigInteger('approver_account_id')->nullable();

            // SHA-256 of canonical body content; set on activation by the service.
            $table->string('content_hash', 64)->nullable();

            $table->timestamps();

            // One version number per template (prevents gaps, enforced at app layer too).
            $table->unique(['template_id', 'version_number'], 'dtv_template_version_unique');
        });

        // Add the deferred active_version_id FK now that the versions table exists.
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->foreign('active_version_id')
                ->references('id')
                ->on('document_template_versions')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('document_templates', function (Blueprint $table): void {
            $table->dropForeign(['active_version_id']);
        });

        Schema::dropIfExists('document_template_versions');
    }
};
