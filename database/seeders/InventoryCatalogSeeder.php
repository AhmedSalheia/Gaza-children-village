<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class InventoryCatalogSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([['EA', 'قطعة', 'Piece'], ['BOX', 'صندوق', 'Box'], ['PK', 'عبوة', 'Pack'], ['KG', 'كيلوغرام', 'Kilogram'], ['L', 'لتر', 'Liter']] as [$c, $a, $e]) DB::table('inventory_units')->updateOrInsert(['code' => $c], ['name_ar' => $a, 'name_en' => $e, 'conversion_to_base' => 1, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([['general', 'أصناف عامة', 'General Items'], ['stationery', 'قرطاسية', 'Stationery'], ['cleaning', 'مواد تنظيف', 'Cleaning Supplies'], ['food', 'مواد غذائية', 'Food Supplies'], ['medical', 'مستلزمات طبية', 'Medical Supplies'], ['equipment', 'معدات', 'Equipment']] as [$c, $a, $e]) DB::table('inventory_categories')->updateOrInsert(['code' => $c], ['name_ar' => $a, 'name_en' => $e, 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }
}
