<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Portal Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /admin and named admin.*.
|
| Protected routes require the 'admin' guard (AdministrativeAccount only).
| Admin sessions are anonymous in the staff and guardian portals.
|
| The portal.version:admin middleware compares the session-stored auth_version
| against the account's current value on every protected request, enabling
| server-side session revocation via RevokePortalAccountSessions.
|
*/

Route::prefix('admin')->name('admin.')->group(function (): void {

    // ── Login / logout (F10) ─────────────────────────────────────────────

    // Show the login form (redirects to dashboard if already authenticated).
    Route::get('/login', [LoginController::class, 'show'])->name('login');

    // Process the login form submission.
    Route::post('/login', [LoginController::class, 'store']);

    // Portal-specific logout — POST only; no GET logout route.
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ── Protected routes ─────────────────────────────────────────────────

    Route::middleware(['auth:admin', 'portal.version:admin'])->group(function (): void {
        Route::get('/dashboard', fn () => view('portals.admin.dashboard'))->name('dashboard');
    });

    // ── Unprotected smoke-test placeholder ────────────────────────────────

    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
