<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parcinfo.dashboard.view', only: ['index']),
        ];
    }

    /**
     * Display the ParcInfo dashboard.
     */
    public function index()
    {
        return app(ParcInfoController::class)->dashboard();
    }
}
