<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Stock\Http\Controllers\Concerns\RepondEnJson;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Services\RapportService;
use Symfony\Component\HttpFoundation\Response;

/**
 * Rapports du module Stock (F8) — quatre rapports filtrables + export PDF.
 */
class RapportController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    private const RAPPORTS = [
        'entrees' => [
            'titre' => 'Rapport des entrées de stock',
            'colonnes' => ['Numéro', 'Type', 'Origine', 'Article', 'Magasin', 'Quantité', 'Coût unitaire', 'Valeur', 'Date'],
            'champs' => ['numero', 'type', 'origine', 'article', 'magasin', 'quantite', 'cout_unitaire', 'valeur', 'date'],
        ],
        'sorties' => [
            'titre' => 'Rapport des sorties de stock',
            'colonnes' => ['Numéro', 'Type', 'Origine', 'Article', 'Magasin', 'Quantité', 'Coût unitaire', 'Valeur', 'Date'],
            'champs' => ['numero', 'type', 'origine', 'article', 'magasin', 'quantite', 'cout_unitaire', 'valeur', 'date'],
        ],
        'transferts' => [
            'titre' => 'Rapport des transferts inter-magasins',
            'colonnes' => ['Numéro', 'Article', 'Source', 'Destination', 'Quantité', 'Statut', 'Date'],
            'champs' => ['numero', 'article', 'source', 'destination', 'quantite', 'statut', 'date'],
        ],
        'stock' => [
            'titre' => 'Rapport du stock par magasin',
            'colonnes' => ['Magasin', 'Code', 'Article', 'Quantité', 'Seuil', 'Alerte', 'Valeur FIFO'],
            'champs' => ['magasin', 'code_article', 'article', 'quantite', 'seuil', 'statut_alerte', 'valeur_fifo'],
        ],
    ];

    public function __construct(protected RapportService $rapportService) {}

    public function index(): View
    {
        $this->authorize('stock.rapports.view');

        return view('stock::rapports.index', [
            'magasins' => Magasin::orderBy('code')->get(['id', 'code', 'libelle', 'statut']),
            'statutsTransfert' => config('stock.statuts_transfert'),
            'statutsAlerte' => config('stock.statuts_alerte'),
        ]);
    }

    public function data(Request $request, string $rapport): JsonResponse
    {
        $this->authorize('stock.rapports.view');

        $lignes = $this->lignes($rapport, $request);

        return $this->table($lignes->count(), $lignes->values());
    }

    public function pdf(Request $request, string $rapport): Response
    {
        $this->authorize('stock.rapports.view');

        $config = self::RAPPORTS[$rapport] ?? abort(404);

        return Pdf::loadView('stock::rapports.pdf', [
            'titre' => $config['titre'],
            'colonnes' => $config['colonnes'],
            'champs' => $config['champs'],
            'lignes' => $this->lignes($rapport, $request),
            'genereLe' => now()->format('d/m/Y H:i'),
        ])->setPaper('a4', 'landscape')->download("rapport-{$rapport}.pdf");
    }

    protected function lignes(string $rapport, Request $request)
    {
        abort_unless(array_key_exists($rapport, self::RAPPORTS), 404);

        $filtres = $request->only([
            'magasin_id', 'type_mouvement', 'article_id', 'date_debut', 'date_fin',
            'magasin_source_id', 'magasin_destination_id', 'statut', 'statut_alerte',
        ]);

        return match ($rapport) {
            'entrees' => $this->rapportService->entrees($filtres),
            'sorties' => $this->rapportService->sorties($filtres),
            'transferts' => $this->rapportService->transferts($filtres),
            'stock' => $this->rapportService->stock($filtres),
        };
    }
}
