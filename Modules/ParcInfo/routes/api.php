<?php

use Illuminate\Support\Facades\Route;
use Modules\ParcInfo\Http\Controllers\ParcInfoController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('parcinfos', ParcInfoController::class)->names('parcinfo');
});
