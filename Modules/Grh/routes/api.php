<?php

use Illuminate\Support\Facades\Route;
use Modules\Grh\Http\Controllers\GrhController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('grhs', GrhController::class)->names('grh');
});
