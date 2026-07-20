<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('web')->group(function (): void {
    Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
});
