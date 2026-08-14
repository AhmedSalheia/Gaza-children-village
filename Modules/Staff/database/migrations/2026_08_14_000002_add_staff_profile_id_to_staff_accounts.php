<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds nullable staff_profile_id to staff_accounts.
 *
 * This column links a StaffAccount (login credential) to the corresponding
 * StaffProfile (employment record). The link is:
 *  - Optional: a StaffProfile may exist without a StaffAccount.
 *  - Unique: at most one StaffAccount per StaffProfile.
 *  - Explicit: the link is made through a controlled action, not automatically.
 *
 * A StaffProfile's person_id must be the same Person referenced by the account;
 * that consistency is maintained at the application layer, not via a DB constraint.
 *
 * Rollback: the column is dropped; existing linked data is lost (migration is
 * safe to reverse only in development environments).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_accounts', function (Blueprint $table): void {
            $table->foreignId('staff_profile_id')
                ->nullable()
                ->unique()
                ->after('id')
                ->constrained('staff_profiles')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('staff_accounts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('staff_profile_id');
        });
    }
};
