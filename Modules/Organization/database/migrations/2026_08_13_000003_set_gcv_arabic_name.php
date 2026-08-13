<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * One-time data migration: set the official approved Arabic name for the
 * GCV organization record on existing deployments.
 *
 * The Arabic name 'قرية أطفال غزة' was confirmed by GCV stakeholders.
 * New deployments receive this value through OrganizationReferenceSeeder.
 * Existing deployments that were already seeded with name_ar = null require
 * this migration to receive the value without a manual admin action.
 *
 * This migration only writes the value when name_ar IS NULL on the gcv record
 * so that any administrator-edited value is never silently overwritten.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('organizations')
            ->where('code', 'gcv')
            ->whereNull('name_ar')
            ->update(['name_ar' => 'قرية أطفال غزة']);
    }

    public function down(): void
    {
        DB::table('organizations')
            ->where('code', 'gcv')
            ->where('name_ar', 'قرية أطفال غزة')
            ->update(['name_ar' => null]);
    }
};
