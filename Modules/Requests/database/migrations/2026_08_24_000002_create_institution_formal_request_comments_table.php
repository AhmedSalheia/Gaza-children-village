<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the institution_formal_request_comments table.
 *
 * Comments are encrypted at rest (application-level encryption via Crypt facade).
 * The audience column controls visibility: internal (institution only),
 * management (management only), or all (visible to both sides).
 *
 * Cross-module integer references (no DB FK constraints):
 *   commenter_account_id → staff_accounts.id or administrative_accounts.id
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('institution_formal_request_comments', function (Blueprint $table): void {
            $table->id();

            // FK to institution_formal_requests
            $table->foreignId('request_id')
                ->constrained('institution_formal_requests')
                ->restrictOnDelete();

            // Actor identity (cross-module plain int)
            $table->string('commenter_actor_type', 32);  // 'staff' | 'administrative'
            $table->unsignedBigInteger('commenter_account_id');
            $table->string('portal', 16);                // 'staff' | 'admin'

            // Audience restriction
            $table->string('audience', 16)->default('all'); // 'internal' | 'management' | 'all'

            // Encrypted content (application-level encryption, never stored as plaintext)
            $table->text('comment_text');

            $table->timestamps();
        });

        Schema::table('institution_formal_request_comments', function (Blueprint $table): void {
            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('institution_formal_request_comments');
    }
};
