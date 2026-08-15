<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Seeds portal accounts for all demo roles.
 *
 * ⚠️  PRODUCTION GUARD: This seeder aborts immediately if the application
 *    is running in production mode. Demo credentials must never be seeded
 *    into a production database.
 *
 * Password: DEMO_SEED_PASSWORD env var, fallback 'demo-password-2026'.
 *
 * Accounts created:
 *   Administrative portal:
 *     - admin@gcv.demo        → system_admin
 *     - calendar@gcv.demo     → calendar_manager
 *     - accounts@gcv.demo     → account_manager
 *
 *   Staff portal (linked to staff profiles via StaffAccount.staff_profile_id):
 *     - principal@gcv.demo    → STAFF-001 (principal)
 *     - secretary@gcv.demo    → STAFF-002 (secretary)
 *     - secretary2@gcv.demo   → STAFF-003 (period-restricted secretary)
 *     - teacher@gcv.demo      → STAFF-004 (teacher)
 *     - counselor@gcv.demo    → STAFF-006 (counselor)
 *
 *   Guardian portal (linked to guardian GRD-002):
 *     - guardian@gcv.demo     → GRD-002
 *
 * Idempotent: checks username/login_identifier before inserting.
 */
final class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        abort_if(
            app()->isProduction(),
            403,
            'DemoAccountSeeder must not run in production. Set APP_ENV=production deliberately disables this seeder.',
        );

        $password = Hash::make(
            env('DEMO_SEED_PASSWORD', 'demo-password-2026')
        );

        $this->seedAdminAccounts($password);
        $this->seedStaffAccounts($password);
        $this->seedGuardianAccounts($password);
    }

    private function seedAdminAccounts(string $passwordHash): void
    {
        $admins = [
            'admin@gcv.demo' => 'system_admin',
            'calendar@gcv.demo' => 'calendar_manager',
            'accounts@gcv.demo' => 'account_manager',
        ];

        foreach ($admins as $username => $roleCode) {
            if (DB::table('administrative_accounts')->where('username', $username)->exists()) {
                continue;
            }

            $accountId = DB::table('administrative_accounts')->insertGetId([
                'username' => $username,
                'password' => $passwordHash,
                'locale_preference' => 'ar',
                'status' => 'active',
                'activated_at' => now(),
                'auth_version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Assign role
            $roleId = DB::table('roles')->where('code', $roleCode)->value('id');

            if ($roleId !== null) {
                DB::table('administrative_account_roles')->insert([
                    'administrative_account_id' => $accountId,
                    'role_id' => $roleId,
                    'granted_by' => 'seeder',
                    'granted_at' => now(),
                    'revoked_at' => null,
                    'revoked_by' => null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function seedStaffAccounts(string $passwordHash): void
    {
        // Map username → staff_code
        $staffAccounts = [
            'principal@gcv.demo' => 'STAFF-001',
            'secretary@gcv.demo' => 'STAFF-002',
            'secretary2@gcv.demo' => 'STAFF-003',
            'teacher@gcv.demo' => 'STAFF-004',
            'teacher2@gcv.demo' => 'STAFF-005',
            'counselor@gcv.demo' => 'STAFF-006',
        ];

        foreach ($staffAccounts as $username => $staffCode) {
            if (DB::table('staff_accounts')->where('username', $username)->exists()) {
                continue;
            }

            $staffProfileId = DB::table('staff_profiles')
                ->where('staff_code', $staffCode)
                ->value('id');

            DB::table('staff_accounts')->insertGetId([
                'username' => $username,
                'password' => $passwordHash,
                'locale_preference' => 'ar',
                'status' => 'active',
                'activated_at' => now(),
                'auth_version' => 0,
                'staff_profile_id' => $staffProfileId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function seedGuardianAccounts(string $passwordHash): void
    {
        $guardianAccounts = [
            'guardian@gcv.demo' => 'GRD-002',
        ];

        foreach ($guardianAccounts as $loginIdentifier => $guardianCode) {
            if (DB::table('guardian_accounts')->where('login_identifier', $loginIdentifier)->exists()) {
                continue;
            }

            $accountId = DB::table('guardian_accounts')->insertGetId([
                'login_identifier' => $loginIdentifier,
                'password' => $passwordHash,
                'locale_preference' => 'ar',
                'status' => 'active',
                'activated_at' => now(),
                'auth_version' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Link guardian account back to guardian profile
            $guardianProfileId = DB::table('guardian_profiles')
                ->where('guardian_code', $guardianCode)
                ->value('id');

            if ($guardianProfileId !== null) {
                DB::table('guardian_profiles')
                    ->where('id', $guardianProfileId)
                    ->update(['guardian_account_id' => $accountId]);
            }
        }
    }
}
