<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `staff_profiles` table.
 *
 * A StaffProfile belongs one-to-one to a Person and represents the employment
 * record for a staff member. It may exist without a StaffAccount (guards and
 * non-login staff are valid StaffProfiles). It may also exist without a current
 * institution assignment.
 *
 * The staff_code is a unique stable human-readable identifier for the staff member.
 *
 * No salary, contracts, HR notes, bank data, health data, or document fields.
 * No semester positions — those are added in F16.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_profiles', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('person_id')->unique()->constrained('people')->restrictOnDelete();
            $table->string('staff_code')->unique();
            $table->string('employment_status')->default('active'); // EmploymentStatus enum
            $table->date('hired_on')->nullable();
            $table->date('ended_on')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_profiles');
    }
};
