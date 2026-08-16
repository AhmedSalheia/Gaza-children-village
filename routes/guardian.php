<?php

declare(strict_types=1);

use App\Http\Controllers\Guardian\IssuedDocumentDownloadController;
use App\Http\Controllers\Guardian\LoginController;
use App\Livewire\Guardian\Corrections\CorrectionDetail;
use App\Livewire\Guardian\Corrections\MyCorrections;
use App\Livewire\Guardian\Corrections\NewCorrectionRequest;
use App\Livewire\Guardian\Dashboard;
use App\Livewire\Guardian\Documents\DocumentRequestDetail;
use App\Livewire\Guardian\Documents\MyDocumentRequests;
use App\Livewire\Guardian\Documents\NewDocumentRequest;
use App\Livewire\Guardian\Students\StudentDetail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Parent/Student Portal Routes
|--------------------------------------------------------------------------
|
| All routes are prefixed with /guardian and named guardian.*.
| The user-facing name is "Parent/Student Portal"; the authenticated account
| belongs to a parent or authorized guardian, never to a student.
|
| Protected routes require the 'guardian' guard (GuardianAccount only).
| Guardian sessions are anonymous in the admin and staff portals.
|
| Guardian authentication grants a guardian account actor identity only.
| No student records are accessible until a future verified guardian-student
| relationship exists (deferred to F13/F15).
|
*/

Route::prefix('guardian')->name('guardian.')->group(function (): void {

    // ── Login / logout (F10) ─────────────────────────────────────────────

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // ── Protected routes ─────────────────────────────────────────────────

    Route::middleware(['auth:guardian', 'portal.version:guardian'])->group(function (): void {
        Route::get('/dashboard', Dashboard::class)->name('dashboard');
        Route::get('/students/{studentProfileId}', StudentDetail::class)->name('students.detail');

        // Correction requests
        Route::get('/corrections', MyCorrections::class)->name('corrections.index');
        Route::get('/corrections/new', NewCorrectionRequest::class)->name('corrections.create');
        Route::get('/corrections/{requestId}', CorrectionDetail::class)
            ->where('requestId', '[0-9]+')
            ->name('corrections.detail');

        // Document requests
        Route::get('/documents', MyDocumentRequests::class)->name('documents.index');
        Route::get('/documents/new', NewDocumentRequest::class)->name('documents.new');
        Route::get('/documents/{requestId}', DocumentRequestDetail::class)
            ->where('requestId', '[0-9]+')
            ->name('documents.detail');
        Route::get('/documents/download/{documentId}', IssuedDocumentDownloadController::class)
            ->where('documentId', '[0-9]+')
            ->name('documents.download');
    });

    // ── Unprotected smoke-test placeholder ────────────────────────────────

    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
