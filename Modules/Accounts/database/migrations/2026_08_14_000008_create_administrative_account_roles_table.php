<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Administrative-account → role grant table.
 *
 * Belongs in the Accounts module because:
 *  - Accounts depends on Authorization ✓
 *  - Authorization has zero allowed deps; it cannot reference account tables.
 *
 * role_id references Authorization.roles — stored as plain integer (no ORM
 * cross-module relationship) to respect the boundary rules.
 *
 * revoked_at = null means the grant is currently active.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('administrative_account_roles', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('administrative_account_id')
                ->constrained('administrative_accounts')
                ->cascadeOnDelete();

            // References Authorization.roles — no FK constraint (cross-module boundary).
            $table->unsignedBigInteger('role_id');

            $table->string('granted_by', 200);
            $table->timestamp('granted_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->string('revoked_by', 200)->nullable();

            $table->timestamps();

            $table->index(['administrative_account_id', 'revoked_at'],
                'admin_acct_roles_acct_revoked_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('administrative_account_roles');
    }
};
