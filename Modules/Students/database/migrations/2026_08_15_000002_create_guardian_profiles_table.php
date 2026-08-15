<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `guardian_profiles` table.
 *
 * GuardianProfile extends Person for guardian-role purposes. A Person may have
 * at most one GuardianProfile (unique index on person_id).
 *
 * guardian_account_id is a plain integer (no DB-level FK) — cross-module
 * reference to the Accounts module's guardian_accounts table. Nullable.
 * Creating a GuardianProfile NEVER creates a GuardianAccount automatically.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('guardian_code', 32)->unique();
            $table->string('lifecycle_status', 32)->default('active');
            // Plain integer cross-module reference — no DB-level constraint.
            $table->unsignedBigInteger('guardian_account_id')->nullable()->index();
            $table->timestamps();

            $table->index('lifecycle_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_profiles');
    }
};
