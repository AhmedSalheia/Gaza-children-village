<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);
use Modules\Accounts\Models\AdministrativeAccount;
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 account schema', function (): void {

    it('creates administrative_accounts table with all required columns', function (): void {
        expect(Schema::hasTable('administrative_accounts'))->toBeTrue();

        foreach (['id', 'username', 'password', 'status', 'activated_at', 'suspended_at', 'locked_at', 'revoked_at', 'remember_token', 'created_at', 'updated_at'] as $column) {
            expect(Schema::hasColumn('administrative_accounts', $column))
                ->toBeTrue("Column '$column' missing from administrative_accounts");
        }
    });

    it('creates staff_accounts table with all required columns', function (): void {
        expect(Schema::hasTable('staff_accounts'))->toBeTrue();

        foreach (['id', 'username', 'password', 'status', 'activated_at', 'suspended_at', 'locked_at', 'revoked_at', 'remember_token', 'created_at', 'updated_at'] as $column) {
            expect(Schema::hasColumn('staff_accounts', $column))
                ->toBeTrue("Column '$column' missing from staff_accounts");
        }
    });

    it('creates guardian_accounts table with all required columns', function (): void {
        expect(Schema::hasTable('guardian_accounts'))->toBeTrue();

        foreach (['id', 'login_identifier', 'password', 'status', 'activated_at', 'suspended_at', 'locked_at', 'revoked_at', 'remember_token', 'created_at', 'updated_at'] as $column) {
            expect(Schema::hasColumn('guardian_accounts', $column))
                ->toBeTrue("Column '$column' missing from guardian_accounts");
        }
    });

    it('guardian_accounts uses login_identifier not username', function (): void {
        expect(Schema::hasColumn('guardian_accounts', 'login_identifier'))->toBeTrue();
        expect(Schema::hasColumn('guardian_accounts', 'username'))->toBeFalse();
    });

    it('does not add soft delete to any account table', function (): void {
        foreach (['administrative_accounts', 'staff_accounts', 'guardian_accounts'] as $table) {
            expect(Schema::hasColumn($table, 'deleted_at'))
                ->toBeFalse("$table must not have soft delete");
        }
    });

    it('does not add person or identity foreign keys in F09', function (): void {
        // staff_profile_id was added to staff_accounts in a post-F09 Staff module migration
        // (linking credential to employment record) — guard updated to exclude it.
        $forbidden = ['person_id', 'national_id', 'student_id'];

        foreach (['administrative_accounts', 'staff_accounts', 'guardian_accounts'] as $table) {
            foreach ($forbidden as $column) {
                expect(Schema::hasColumn($table, $column))
                    ->toBeFalse("$table must not have $column in F09");
            }
        }
    });

    it('does not add fake audit columns in F09', function (): void {
        foreach (['administrative_accounts', 'staff_accounts', 'guardian_accounts'] as $table) {
            expect(Schema::hasColumn($table, 'created_by'))->toBeFalse();
            expect(Schema::hasColumn($table, 'updated_by'))->toBeFalse();
        }
    });

    it('enforces portal-local username uniqueness — same username may exist in admin and staff tables', function (): void {
        AdministrativeAccount::factory()->withUsername('shared_user')->create();
        StaffAccount::factory()->withUsername('shared_user')->create();

        expect(AdministrativeAccount::where('username', 'shared_user')->count())->toBe(1);
        expect(StaffAccount::where('username', 'shared_user')->count())->toBe(1);
    });

    it('enforces unique username within administrative_accounts', function (): void {
        AdministrativeAccount::factory()->withUsername('taken_admin')->create();

        expect(fn () => AdministrativeAccount::factory()->withUsername('taken_admin')->create())
            ->toThrow(Exception::class);
    });

    it('enforces unique username within staff_accounts', function (): void {
        StaffAccount::factory()->withUsername('taken_staff')->create();

        expect(fn () => StaffAccount::factory()->withUsername('taken_staff')->create())
            ->toThrow(Exception::class);
    });

    it('enforces unique login_identifier within guardian_accounts', function (): void {
        GuardianAccount::factory()->withLoginIdentifier('taken-id-001')->create();

        expect(fn () => GuardianAccount::factory()->withLoginIdentifier('taken-id-001')->create())
            ->toThrow(Exception::class);
    });

    it('creates portal password reset token tables', function (): void {
        foreach (['admin_password_reset_tokens', 'staff_password_reset_tokens', 'guardian_password_reset_tokens'] as $table) {
            expect(Schema::hasTable($table))->toBeTrue("Table $table is missing");
            expect(Schema::hasColumn($table, 'identifier'))->toBeTrue();
            expect(Schema::hasColumn($table, 'token'))->toBeTrue();
            expect(Schema::hasColumn($table, 'created_at'))->toBeTrue();
        }
    });

});
