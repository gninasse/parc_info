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

        // Stats Dépenses par Mois
        $expendituresByMonth = BonCommande::whereIn('statut', ['valide', 'partiel', 'livre'])
            ->selectRaw("TO_CHAR(date_commande, 'YYYY-MM') as month, SUM(montant_total) as total")
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->limit(6)
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->month,
                    'total' => (float) $item->total,
                ];
            });

        // Stats Dépenses par Fournisseur
        $expendituresBySupplier = BonCommande::whereIn('statut', ['valide', 'partiel', 'livre'])
            ->join('parc_info_fournisseurs', 'achat_bons_commande.fournisseur_id', '=', 'parc_info_fournisseurs.id')
            ->selectRaw('parc_info_fournisseurs.nom as supplier_name, SUM(montant_total) as total')
            ->groupBy('parc_info_fournisseurs.nom')
            ->orderBy('total', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->supplier_name,
                    'total' => (float) $item->total,
                ];
            });

        // Répartition catalogue
        $articlesByType = Article::selectRaw('type_article, COUNT(*) as count')
            ->groupBy('type_article')
            ->get()
            ->map(function ($item) {
                $labels = [
                    'equipement' => 'Équipements',
                    'licence' => 'Licences',
                    'consommable' => 'Consommables',
                ];

                return [
                    'label' => $labels[$item->type_article] ?? $item->type_article,
                    'count' => $item->count,
                ];
            });

        return view('achat::index', compact(
            'totalArticles',
            'totalBC',
            'bcBrouillon',
            'bcValide',
            'bcLivre',
            'totalBL',
            'blBrouillon',
            'blValide',
            'stockAlerts',
            'expendituresByMonth',
            'expendituresBySupplier',
            'articlesByType'
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
