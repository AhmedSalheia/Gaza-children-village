<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Organization\Database\Seeders\InstitutionTypeReferenceSeeder;
use Modules\Organization\Database\Seeders\OrganizationReferenceSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Organization reference data must be seeded before institution types
        // because institutions (F04) will reference both tables.
        $this->call([
            OrganizationReferenceSeeder::class,
            InstitutionTypeReferenceSeeder::class,
        ]);
    }
}
