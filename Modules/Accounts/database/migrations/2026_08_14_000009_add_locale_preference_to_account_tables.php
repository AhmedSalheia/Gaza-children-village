<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add locale_preference column to all three account tables.
 *
 * Values: 'ar' (default) | 'en'
 * Null means "use session/default" — treated the same as 'ar'.
 *
 * Kept nullable so existing rows do not require immediate backfilling.
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['administrative_accounts', 'staff_accounts', 'guardian_accounts'] as $table) {
            Schema::table($table, function (Blueprint $t) use ($table): void {
                if (! Schema::hasColumn($table, 'locale_preference')) {
                    $t->string('locale_preference', 10)->nullable()->after('email');
                }
            });
        }
    }

    public function down(): void
    {
        foreach (['administrative_accounts', 'staff_accounts', 'guardian_accounts'] as $table) {
            Schema::table($table, function (Blueprint $t): void {
                $t->dropColumn('locale_preference');
            });
        }
    }
};
