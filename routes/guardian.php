<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent/Student Portal Routes
|--------------------------------------------------------------------------
|
| All routes in this file are prefixed with /guardian and named guardian.*.
| The user-facing name is "Parent/Student Portal", but the authenticated
| account belongs to a parent or authorized guardian, never to a student.
|
| Protected routes require the 'guardian' guard, which authenticates only
| GuardianAccount models. Guardian sessions are anonymous in the admin and
| staff portals.
|
| Guardian authentication grants no student access until a future verified
| guardian-student relationship exists (deferred to F13/F15).
|
| Login, logout, and account-management routes are deferred to F10/F11.
|
*/

Route::prefix('guardian')->name('guardian.')->group(function (): void {

    // Protected dashboard — requires Guardian Portal authentication.
    Route::middleware(['auth:guardian'])->group(function (): void {
        Route::get('/dashboard', fn () => view('portals.guardian.dashboard'))->name('dashboard');
    });

    // Unprotected placeholder (for smoke tests).
    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
