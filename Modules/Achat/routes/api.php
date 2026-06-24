<?php

use Illuminate\Support\Facades\Route;
use Modules\Achat\Http\Controllers\AchatController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('achats', AchatController::class)->names('achat');
});
