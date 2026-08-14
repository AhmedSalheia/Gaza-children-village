<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `people` table.
 *
 * A Person is the stable canonical record for one real human being.
 * It has a surrogate primary key that never changes, regardless of identifier
 * corrections, name changes, or profile updates.
 *
 * No institution, semester, account, role, or position fields are stored here.
 * Those belong to profile and assignment records added in later phases.
 *
 * Gender, marital status, religion, disability, health, address, deceased state,
 * and civil-registry fields are explicitly deferred (see ADR F12).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->string('full_name_ar');
            $table->string('full_name_en')->nullable();
            $table->date('birth_date')->nullable();
            // Precision qualifier for birth_date: exact | month | year | unknown
            $table->string('birth_date_precision')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
