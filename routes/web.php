<?php

use App\Http\Controllers\LocaleSwitchController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// F20 — locale switch (CSRF-protected, portal-agnostic).
Route::post('/locale-switch', LocaleSwitchController::class)->name('locale.switch');
