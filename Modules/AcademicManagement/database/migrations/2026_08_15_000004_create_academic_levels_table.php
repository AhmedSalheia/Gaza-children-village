<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Creates the `academic_levels` table.
 *
 * AcademicLevel is a global reference catalogue (KG1, KG2, Grade1–Grade12).
 * No assumption is made about the number of grades or naming; the seeder
 * populates the GCV-specific set.
 *
 * No institution-type junction at this level — availability per institution
 * type is a future extension. is_active controls global visibility.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('academic_levels', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 32)->unique();
            $table->string('name_en');
            $table->string('name_ar');
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('sequence');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_levels');
    }
};
