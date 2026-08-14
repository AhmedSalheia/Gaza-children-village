<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes in this file are prefixed with /admin and named admin.*.
|
| Protected routes require the 'admin' guard, which authenticates only
| AdministrativeAccount models. Admin sessions are anonymous in the staff
| and guardian portals.
|
| Login, logout, and account-management routes are deferred to F10/F11.
|
*/

Route::prefix('admin')->name('admin.')->group(function (): void {

    // Protected dashboard — requires Admin Portal authentication.
    // Admin credentials authenticated through the staff or guardian guard
    // will be rejected (the request will be treated as unauthenticated).
    Route::middleware(['auth:admin'])->group(function (): void {
        Route::get('/dashboard', fn () => view('portals.admin.dashboard'))->name('dashboard');
    });

    // Unprotected placeholder (for smoke tests).
    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
