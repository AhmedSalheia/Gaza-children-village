<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `classrooms` table.
 *
 * A Classroom is a physical or virtual room within an Institution. The stable
 * code is unique within the institution (not globally). institution_id is a
 * plain integer — cross-module reference to Organization.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            // Plain integer — cross-module reference to Organization.institutions.
            $table->unsignedBigInteger('institution_id');
            $table->string('code', 32);
            $table->string('name_en')->nullable();
            $table->string('name_ar');
            $table->unsignedSmallInteger('capacity')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Code is stable and unique within the institution.
            $table->unique(['institution_id', 'code']);
            $table->index('institution_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
