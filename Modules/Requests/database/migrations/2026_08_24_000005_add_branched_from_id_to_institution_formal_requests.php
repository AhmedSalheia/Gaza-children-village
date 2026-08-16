<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds branched_from_id to institution_formal_requests.
 *
 * When a returned request is resubmitted (resubmit()), a new draft row is
 * created and branched_from_id points back to the source row that was returned.
 * The source transitions to STATUS_SUPERSEDED and its superseded_by_id points
 * forward to the new draft — preserving the original revision as an immutable
 * snapshot for audit purposes.
 *
 * This separates "version branching on resubmit" (branched_from_id) from
 * "supersession after management decision" (superseded_by_id) — both produce
 * a STATUS_SUPERSEDED source and a new draft, but from different workflow stages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_formal_requests', function (Blueprint $table): void {
            $table->unsignedBigInteger('branched_from_id')
                ->nullable()
                ->after('superseded_by_id');

            $table->index('branched_from_id', 'ifr_branched_from_idx');
        });
    }

    public function down(): void
    {
        Schema::table('institution_formal_requests', function (Blueprint $table): void {
            $table->dropIndex('ifr_branched_from_idx');
            $table->dropColumn('branched_from_id');
        });
    }
};
