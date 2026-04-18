<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth.session'])->group(function (): void {
    // Exemple d'usage : Route::get('/profile', [ProfileController::class, 'show']);
});

Route::middleware(['auth.session', 'ensure.global.admin'])->group(function (): void {
    // Exemple d'usage : Route::post('/admin/ban', [AdminController::class, 'ban']);
});
