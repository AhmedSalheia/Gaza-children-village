<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replace the global unique index on request_number with a composite unique
 * on (institution_id, request_number).
 *
 * The number service generates sequential numbers *per institution*, so two
 * institutions can legitimately both hold GCV-FR-2026-00001.  A global unique
 * constraint incorrectly prevents the second institution from ever creating
 * its first request of the year.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('institution_formal_requests', function (Blueprint $table): void {
            $table->dropUnique(['request_number']);
            $table->unique(['institution_id', 'request_number'], 'ifr_institution_number_unique');
        });
    }

    public function down(): void
    {
        Schema::table('institution_formal_requests', function (Blueprint $table): void {
            $table->dropUnique('ifr_institution_number_unique');
            $table->unique('request_number');
        });
    }
};
