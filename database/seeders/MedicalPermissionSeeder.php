<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MedicalPermissionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Medical Role
        |--------------------------------------------------------------------------
        */

        $roleId = DB::table('roles')->where('code', 'medical_manager')->value('id');

        if ($roleId === null) {
            $roleId = DB::table('roles')->insertGetId([
                'code' => 'medical_manager',
                'label' => 'Medical Manager',
                'is_protected' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Medical Permissions
        |--------------------------------------------------------------------------
        */

        $keys = [
            'medical.view',

            'medical.clinic.view',
            'medical.clinic.manage',

            'medical.staff.view',
            'medical.staff.manage',

            'medical.patient.view',
            'medical.patient.manage',

            'medical.visit.view',
            'medical.visit.create',
            'medical.visit.update',

            'medical.medicine.view',
            'medical.medicine.manage',
            'medical.medicine.receipt',
            'medical.medicine.issue',
            'medical.medicine.adjust',

            'medical.prescription.view',
            'medical.prescription.create',
            'medical.prescription.dispense',

            'medical.report.view',
            'medical.export',
        ];

        /*
        |--------------------------------------------------------------------------
        | Create / Update Permissions
        |--------------------------------------------------------------------------
        */

        foreach ($keys as $key) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $key],
                [
                    'description' => 'Medical portal permission: ' . $key,
                    'group' => 'medical',
                    'is_system' => 1,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Attach Medical Permissions to medical_manager
        |--------------------------------------------------------------------------
        */

        $permissionIds = DB::table('permissions')
            ->whereIn('key', $keys)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insertOrIgnore([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
            ]);
        }
    }
}
