<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::prefix('staff')->name('staff.')->group(function (): void {
    Route::get('/', static fn () => response()->noContent())->name('placeholder');
});
