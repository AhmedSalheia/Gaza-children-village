<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add an auth_version counter to all three account tables.
 *
 * The auth_version column supports the session-revocation primitive (F10):
 * incrementing this value invalidates all existing sessions for that account
 * without touching any other portal's session data.
 *
 * The VerifyPortalSessionVersion middleware compares the version stored in
 * the portal-specific session key against the account's current auth_version
 * on every protected request.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('administrative_accounts', function (Blueprint $table): void {
            $table->unsignedInteger('auth_version')->default(0)->after('revoked_at');
        });

        Schema::table('staff_accounts', function (Blueprint $table): void {
            $table->unsignedInteger('auth_version')->default(0)->after('revoked_at');
        });

        Schema::table('guardian_accounts', function (Blueprint $table): void {
            $table->unsignedInteger('auth_version')->default(0)->after('revoked_at');
        });
    }

    public function down(): void
    {
        Schema::table('administrative_accounts', function (Blueprint $table): void {
            $table->dropColumn('auth_version');
        });

        Schema::table('staff_accounts', function (Blueprint $table): void {
            $table->dropColumn('auth_version');
        });

        Schema::table('guardian_accounts', function (Blueprint $table): void {
            $table->dropColumn('auth_version');
        });
    }
};
