<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\ParcInfo\Models\Fournisseur;
use Symfony\Component\HttpFoundation\Response;

class StatistiquesController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $this->authorize('achat.dashboard.view');

        $fournisseurs = Fournisseur::orderBy('nom')->get();

        // 1. Volumes globaux
        $totalCommandes = BonCommande::count();
        $totalMontantAchats = BonCommande::whereIn('statut', ['valide', 'partiel', 'livre'])->sum('montant_total');

        // Taux de complétion des livraisons (lignes totalement livrées / total lignes de commande validées)
        $totalLignes = LigneCommande::whereHas('bonCommande', function ($query) {
            $query->whereIn('statut', ['valide', 'partiel', 'livre']);
        })->count();

        $lignesLivrees = LigneCommande::whereHas('bonCommande', function ($query) {
            $query->whereIn('statut', ['valide', 'partiel', 'livre']);
        })->whereColumn('quantite_livree', '>=', 'quantite')->count();

        $completionRate = $totalLignes > 0 ? round(($lignesLivrees / $totalLignes) * 100, 1) : 100.0;

        // 2. Évolution mensuelle (derniers 6 mois)
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

        // 3. Répartition par fournisseur (Top 5)
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

        // 4. Répartition par type d'article
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

        return view('achat::statistiques.index', compact(
            'fournisseurs',
            'totalCommandes',
            'totalMontantAchats',
            'completionRate',
            'expendituresByMonth',
            'expendituresBySupplier',
            'articlesByType'
        ));
    }

    /**
     * Get statistics data for table representation.
     */
    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.dashboard.view');

        $reportType = $request->get('report_type', 'global_purchases');
        $data = $this->getReportData($reportType, $request);

        return response()->json([
            'success' => true,
            'rows' => $data['rows'],
            'columns' => $data['columns'],
            'title' => $data['title'],
        ]);
    }

    /**
     * Generate PDF stream for the reports.
     */
    public function generatePdf(Request $request): Response
    {
        $this->authorize('achat.dashboard.view');

        $reportType = $request->get('report_type', 'global_purchases');
        $data = $this->getReportData($reportType, $request);

        $title = $data['title'];
        $columns = $data['columns'];
        $rows = $data['rows'];

        $filters = [];
        if ($request->filled('fournisseur_id') && $fourn = Fournisseur::find($request->fournisseur_id)) {
            $filters['Fournisseur'] = $fourn->nom;
        }
        if ($request->filled('statut')) {
            $filters['Statut'] = ucfirst($request->statut);
        }
        if ($request->filled('date_debut')) {
            $filters['Date début'] = \Carbon\Carbon::parse($request->date_debut)->format('d/m/Y');
        }
        if ($request->filled('date_fin')) {
            $filters['Date fin'] = \Carbon\Carbon::parse($request->date_fin)->format('d/m/Y');
        }

        $pdf = Pdf::loadView('achat::statistiques.pdf', compact('title', 'columns', 'rows', 'filters'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream(str_replace(' ', '_', strtolower($title)).'.pdf');
    }

    /**
     * Helper to prepare report data and headers.
     */
    protected function getReportData(string $reportType, Request $request): array
    {
        $title = 'Rapport Achat';
        $columns = [];
        $rows = [];

        switch ($reportType) {
            case 'global_purchases':
                $title = 'Rapport Global des Bons de Commande';
                $columns = [
                    'numero_commande' => 'N° Commande',
                    'date_commande' => 'Date Commande',
                    'fournisseur' => 'Fournisseur',
                    'montant_total' => 'Montant Total',
                    'statut' => 'Statut',
                ];

                $query = BonCommande::with('fournisseur')->orderBy('date_commande', 'desc');

                // Filters
                if ($request->filled('fournisseur_id')) {
                    $query->where('fournisseur_id', $request->fournisseur_id);
                }
                if ($request->filled('statut')) {
                    $query->where('statut', $request->statut);
                }
                if ($request->filled('date_debut')) {
                    $query->whereDate('date_commande', '>=', $request->date_debut);
                }
                if ($request->filled('date_fin')) {
                    $query->whereDate('date_commande', '<=', $request->date_fin);
                }

                $rows = $query->get()->map(function ($bc) {
                    return [
                        'numero_commande' => $bc->numero_commande,
                        'date_commande' => $bc->date_commande ? $bc->date_commande->format('d/m/Y') : '-',
                        'fournisseur' => $bc->fournisseur?->nom ?? '-',
                        'montant_total' => number_format($bc->montant_total, 0, ',', ' ').' FCFA',
                        'statut' => ucfirst($bc->statut),
                    ];
                })->toArray();
                break;

            case 'by_supplier_detail':
                $title = 'Rapport de Dépenses par Fournisseur';
                $columns = [
                    'fournisseur' => 'Fournisseur',
                    'nombre_commandes' => 'Nbre Commandes',
                    'montant_total' => 'Montant Total Commandé',
                ];

                $query = Fournisseur::leftJoin('achat_bons_commande', 'parc_info_fournisseurs.id', '=', 'achat_bons_commande.fournisseur_id')
                    ->selectRaw('parc_info_fournisseurs.nom as name, COUNT(achat_bons_commande.id) as count, SUM(achat_bons_commande.montant_total) as total')
                    ->whereNull('achat_bons_commande.deleted_at')
                    ->groupBy('parc_info_fournisseurs.nom')
                    ->orderBy('total', 'desc');

                if ($request->filled('fournisseur_id')) {
                    $query->where('parc_info_fournisseurs.id', $request->fournisseur_id);
                }

                $rows = $query->get()->map(function ($item) {
                    return [
                        'fournisseur' => $item->name,
                        'nombre_commandes' => $item->count,
                        'montant_total' => number_format((float) $item->total, 0, ',', ' ').' FCFA',
                    ];
                })->toArray();
                break;

            case 'reliquats':
                $title = 'Rapport des Reliquats de Livraison';
                $columns = [
                    'numero_commande' => 'N° Commande',
                    'article' => 'Article',
                    'quantite_commandee' => 'Qté Commandée',
                    'quantite_livree' => 'Qté Livrée',
                    'reliquat' => 'Reliquat Restant',
                ];

                $query = LigneCommande::with(['bonCommande', 'article'])
                    ->whereHas('bonCommande', function ($q) {
                        $q->whereIn('statut', ['valide', 'partiel']);
                    })
                    ->whereColumn('quantite_livree', '<', 'quantite');

                if ($request->filled('fournisseur_id')) {
                    $query->whereHas('bonCommande', function ($q) use ($request) {
                        $q->where('fournisseur_id', $request->fournisseur_id);
                    });
                }

                $rows = $query->get()->map(function ($line) {
                    return [
                        'numero_commande' => $line->bonCommande->numero_commande,
                        'article' => $line->article?->designation ?? '-',
                        'quantite_commandee' => $line->quantite,
                        'quantite_livree' => $line->quantite_livree,
                        'reliquat' => $line->quantite - $line->quantite_livree,
                    ];
                })->toArray();
                break;

            case 'popular_articles':
                $title = 'Articles les plus commandés';
                $columns = [
                    'designation' => 'Désignation',
                    'type' => 'Type',
                    'quantite_totale' => 'Quantité Totale Commandée',
                    'montant_total' => 'Montant Total Cumulé',
                ];

                $query = LigneCommande::join('achat_articles', 'achat_lignes_commande.article_id', '=', 'achat_articles.id')
                    ->selectRaw('achat_articles.designation, achat_articles.type_article, SUM(quantite) as qty, SUM(quantite * prix_unitaire) as total')
                    ->groupBy('achat_articles.designation', 'achat_articles.type_article')
                    ->orderBy('qty', 'desc');

                $typesMap = [
                    'equipement' => 'Équipement',
                    'licence' => 'Licence',
                    'consommable' => 'Consommable',
                ];

                $rows = $query->get()->map(function ($item) use ($typesMap) {
                    return [
                        'designation' => $item->designation,
                        'type' => $typesMap[$item->type_article] ?? $item->type_article,
                        'quantite_totale' => $item->qty,
                        'montant_total' => number_format((float) $item->total, 0, ',', ' ').' FCFA',
                    ];
                })->toArray();
                break;
        }

        return [
            'title' => $title,
            'columns' => $columns,
            'rows' => $rows,
        ];
    }
}
