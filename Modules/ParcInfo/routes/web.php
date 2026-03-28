<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\ParcInfoController;

Route::middleware(['auth'])->prefix('parc-info')->name('parc-info.')->group(function () {
    Route::get('/dashboard', [ParcInfoController::class, 'dashboard'])->name('dashboard');
});
