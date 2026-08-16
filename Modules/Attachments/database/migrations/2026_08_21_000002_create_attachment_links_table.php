<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `attachment_links` polymorphic join table.
 *
 * Allows any domain entity (correction request, formal request, etc.) to
 * be linked to one or more secure attachments without coupling the
 * secure_attachments table to any specific domain model.
 *
 * A single attachment may be linked to multiple entities (e.g. a correction
 * request and its audit case). The link_type column distinguishes purpose
 * within the linkable entity (e.g. 'supporting_evidence', 'government_id').
 *
 * No updated_at — links are append-only. To unlink, soft-delete the
 * linkable entity; do not delete attachment rows (audit trail).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attachment_links', function (Blueprint $table): void {
            $table->id();

            // The domain entity this attachment belongs to
            $table->string('linkable_type', 128);
            $table->unsignedBigInteger('linkable_id');

            // The attachment being linked
            $table->uuid('attachment_id');
            $table->foreign('attachment_id')
                ->references('id')
                ->on('secure_attachments')
                ->restrictOnDelete();

            // Semantic role within the linkable entity
            $table->string('link_type', 64)->default('supporting_evidence');

            $table->timestamp('created_at')->useCurrent();

            // Composite index for fast lookup by entity
            $table->index(['linkable_type', 'linkable_id'], 'al_linkable_idx');

            // Prevent exact duplicate links
            $table->unique(
                ['linkable_type', 'linkable_id', 'attachment_id', 'link_type'],
                'al_unique_link'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachment_links');
    }
};
