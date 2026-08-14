<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Portal Routes
|--------------------------------------------------------------------------
|
| All routes in this file are prefixed with /staff and named staff.*.
|
| Protected routes require the 'staff' guard, which authenticates only
| StaffAccount models. Staff sessions are anonymous in the admin and
| guardian portals.
|
| Staff authentication grants no institution data access on its own.
| Institutional operational access additionally requires eligible active
| positions, F02 trusted operational context, and Authorization policies.
|
| Login, logout, and account-management routes are deferred to F10/F11.
|
*/

Route::prefix('staff')->name('staff.')->group(function (): void {

    // Protected dashboard — requires Staff Portal authentication.
    Route::middleware(['auth:staff'])->group(function (): void {
        Route::get('/dashboard', fn () => view('portals.staff.dashboard'))->name('dashboard');
    });

    // Unprotected placeholder (for smoke tests).
    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
