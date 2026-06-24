<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;

class AchatController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $this->authorize('achat.dashboard.view');

        $totalArticles = Article::count();
        $totalBC = BonCommande::count();
        $bcBrouillon = BonCommande::where('statut', 'brouillon')->count();
        $bcValide = BonCommande::where('statut', 'valide')->count();
        $bcLivre = BonCommande::whereIn('statut', ['partiel', 'livre'])->count();

        $totalBL = BordereauLivraison::count();
        $blBrouillon = BordereauLivraison::whereIn('statut', ['brouillon', 'wizard'])->count();
        $blValide = BordereauLivraison::where('statut', 'valide')->count();

        $stockAlerts = Article::where('type_article', 'consommable')
            ->whereRaw('stock_actuel <= seuil_alerte')
            ->count();

        return view('achat::index', compact(
            'totalArticles',
            'totalBC',
            'bcBrouillon',
            'bcValide',
            'bcLivre',
            'totalBL',
            'blBrouillon',
            'blValide',
            'stockAlerts'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('achat::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('achat::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('achat::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id) {}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id) {}
}
