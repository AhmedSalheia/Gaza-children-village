<?php

use App\Http\Controllers\Attendance\ScanController;
use App\Http\Controllers\LocaleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// F20 — locale switch (CSRF-protected, portal-agnostic).
Route::post('/locale-switch', LocaleSwitchController::class)->name('locale.switch');

// ── QR attendance scan endpoint (public, rate-limited) ────────────────────
//
// CSRF exemption: this endpoint accepts POST from QR scanning devices and the
// manual fallback form. It is exempt from CSRF because:
//  1. Scanning devices cannot hold a CSRF session cookie.
//  2. The token itself acts as the authentication secret.
//  3. The endpoint is idempotent (replay-prevention in SubmitScanEvent).
//
// Add App\Http\Middleware\VerifyCsrfToken::$except[] = 'attend' if CSRF
// middleware is applied globally (check App\Http\Kernel).
Route::post('/attend', [ScanController::class, 'store'])
    ->middleware('throttle:60,1')
    ->name('attend');
