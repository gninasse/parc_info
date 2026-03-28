<?php

use Illuminate\Support\Facades\Route;
use Modules\Grh\Http\Controllers\EmployeController;

Route::middleware(['auth'])->prefix('grh')->name('grh.')->group(function () {
    Route::prefix('employes')->name('employes.')->group(function () {
        Route::get('/', [EmployeController::class, 'index'])->name('index');
        Route::get('/data', [EmployeController::class, 'getData'])->name('data');
        Route::post('/', [EmployeController::class, 'store'])->name('store');
        Route::get('/{id}', [EmployeController::class, 'show'])->name('show');
        Route::put('/{id}', [EmployeController::class, 'update'])->name('update');
        Route::post('/{id}/toggle-status', [EmployeController::class, 'toggleStatus'])->name('toggle-status');
    });
});
