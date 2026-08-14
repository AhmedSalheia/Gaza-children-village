<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Accounts\Models\AdministrativeAccount;

uses(RefreshDatabase::class);
use Modules\Accounts\Models\GuardianAccount;
use Modules\Accounts\Models\StaffAccount;

describe('F09 portal route boundaries', function (): void {

    describe('admin portal', function (): void {

        it('authenticated admin session accesses admin dashboard', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();

            $this->actingAs($account, 'admin')
                ->get('/admin/dashboard')
                ->assertStatus(200);
        });

        it('admin session is anonymous in the staff portal', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();

            $this->actingAs($account, 'admin')
                ->get('/staff/dashboard')
                ->assertStatus(401);
        });

        it('admin session is anonymous in the guardian portal', function (): void {
            $account = AdministrativeAccount::factory()->active()->create();

            $this->actingAs($account, 'admin')
                ->get('/guardian/dashboard')
                ->assertStatus(401);
        });

        it('unauthenticated request to admin dashboard is denied', function (): void {
            $this->get('/admin/dashboard')->assertStatus(401);
        });

    });

    describe('staff portal', function (): void {

        it('authenticated staff session accesses staff dashboard', function (): void {
            $account = StaffAccount::factory()->active()->create();

            $this->actingAs($account, 'staff')
                ->get('/staff/dashboard')
                ->assertStatus(200);
        });

        it('staff session is anonymous in the admin portal', function (): void {
            $account = StaffAccount::factory()->active()->create();

            $this->actingAs($account, 'staff')
                ->get('/admin/dashboard')
                ->assertStatus(401);
        });

        it('staff session is anonymous in the guardian portal', function (): void {
            $account = StaffAccount::factory()->active()->create();

            $this->actingAs($account, 'staff')
                ->get('/guardian/dashboard')
                ->assertStatus(401);
        });

        it('unauthenticated request to staff dashboard is denied', function (): void {
            $this->get('/staff/dashboard')->assertStatus(401);
        });

    });

    describe('guardian portal', function (): void {

        it('authenticated guardian session accesses guardian dashboard', function (): void {
            $account = GuardianAccount::factory()->active()->create();

            $this->actingAs($account, 'guardian')
                ->get('/guardian/dashboard')
                ->assertStatus(200);
        });

        it('guardian session is anonymous in the admin portal', function (): void {
            $account = GuardianAccount::factory()->active()->create();

            $this->actingAs($account, 'guardian')
                ->get('/admin/dashboard')
                ->assertStatus(401);
        });

        it('guardian session is anonymous in the staff portal', function (): void {
            $account = GuardianAccount::factory()->active()->create();

            $this->actingAs($account, 'guardian')
                ->get('/staff/dashboard')
                ->assertStatus(401);
        });

        it('unauthenticated request to guardian dashboard is denied', function (): void {
            $this->get('/guardian/dashboard')->assertStatus(401);
        });

    });

    describe('unprotected placeholders are accessible', function (): void {

        it('admin placeholder is accessible without authentication', function (): void {
            $this->get('/admin')->assertStatus(204);
        });

        it('staff placeholder is accessible without authentication', function (): void {
            $this->get('/staff')->assertStatus(204);
        });

        it('guardian placeholder is accessible without authentication', function (): void {
            $this->get('/guardian')->assertStatus(204);
        });

    });

});
