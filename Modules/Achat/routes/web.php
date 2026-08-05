<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\DashboardController;

/*
|--------------------------------------------------------------------------
| Routes du module Achat (SFD §8)
|--------------------------------------------------------------------------
| Préfixe /achat, noms achat.*, `auth` + permission SERVEUR sur TOUTES les
| routes (.data, cascades, PDF, exports, API compris) ; routes littérales
| avant /{id}.
*/

Route::middleware(['auth'])->prefix('achat')->name('achat.')->group(function () {
    // A-01 — Tableau de bord
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
});
