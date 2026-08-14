<?php

declare(strict_types=1);

use App\Http\Controllers\Staff\LoginController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Staff Portal Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /staff and named staff.*.
|
| Protected routes require the 'staff' guard (StaffAccount only).
| Staff sessions are anonymous in the admin and guardian portals.
|
| Staff authentication grants a staff account actor identity only.
| Institutional operational access additionally requires eligible active
| positions, F02 trusted operational context, and Authorization policies
| (deferred to F13 and later).
|
*/

Route::prefix('staff')->name('staff.')->group(function (): void {

    // ── Login / logout (F10) ─────────────────────────────────────────────

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ── Protected routes ─────────────────────────────────────────────────

    Route::middleware(['auth:staff', 'portal.version:staff'])->group(function (): void {
        Route::get('/dashboard', fn () => view('portals.staff.dashboard'))->name('dashboard');
    });

    // ── Unprotected smoke-test placeholder ────────────────────────────────

    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
