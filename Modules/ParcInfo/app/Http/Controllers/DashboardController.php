<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Display the ParcInfo dashboard.
     */
    public function index()
    {
        if (! auth()->user()->can('parcinfo.dashboard.view')) {
            // abort(403);
        }

        return app(ParcInfoController::class)->dashboard();
    }
}
