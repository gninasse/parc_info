<?php

namespace Modules\Grh\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Grh\Models\Employe;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $stats = [
            'total_employes' => Employe::count(),
            'actifs' => Employe::where('est_actif', true)->count(),
            'inactifs' => Employe::where('est_actif', false)->count(),
            'hommes' => Employe::where('genre', 'M')->count(),
            'femmes' => Employe::where('genre', 'F')->count(),
            'par_niveau' => [
                'direction' => Employe::where('niveau_rattachement', 'direction')->count(),
                'service' => Employe::where('niveau_rattachement', 'service')->count(),
                'unite' => Employe::where('niveau_rattachement', 'unite')->count(),
            ],
        ];

        return view('grh::dashboard', compact('stats'));
    }
}
