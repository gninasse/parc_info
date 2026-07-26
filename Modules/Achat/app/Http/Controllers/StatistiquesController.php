<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\StatistiquesService;
use Modules\ParcInfo\Models\Fournisseur;
use Symfony\Component\HttpFoundation\Response;

/**
 * États et statistiques (E-15 / E-16).
 *
 * Correction AN-13 : l'écran contrôle achat.rapports.view et l'export
 * achat.rapports.export, et non plus la permission du tableau de bord.
 */
class StatistiquesController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    /** Définition des quatre états standard. */
    protected const RAPPORTS = [
        'global_purchases' => [
            'titre' => 'Rapport global des bons de commande',
            'filtres' => ['fournisseur', 'statut', 'periode'],
        ],
        'by_supplier_detail' => [
            'titre' => 'Rapport de dépenses par fournisseur',
            'filtres' => ['fournisseur'],
        ],
        'reliquats' => [
            'titre' => 'Rapport des reliquats de livraison',
            'filtres' => ['fournisseur'],
        ],
        'popular_articles' => [
            'titre' => 'Articles les plus commandés',
            'filtres' => [],
        ],
    ];

    public function __construct(protected StatistiquesService $statistiques) {}

    public function index(): View
    {
        $this->authorize('achat.rapports.view');

        return view('achat::statistiques.index', [
            'fournisseurs' => Fournisseur::orderBy('nom')->get(),
            'indicateurs' => $this->statistiques->indicateurs(),
            'depensesMensuelles' => $this->statistiques->depensesMensuelles(),
            'depensesParFournisseur' => $this->statistiques->depensesParFournisseur(),
            'articlesParType' => $this->statistiques->articlesParType(),
            'rapports' => collect(self::RAPPORTS)->map(fn ($r, $cle) => [
                'cle' => $cle,
                'titre' => $r['titre'],
                'filtres' => $r['filtres'],
            ])->values(),
            'statuts' => config('achat.statuts_bc'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.rapports.view');

        $rapport = $this->construireRapport($request);

        return response()->json([
            'success' => true,
            'title' => $rapport['titre'],
            'columns' => $rapport['colonnes'],
            'rows' => $rapport['lignes'],
        ]);
    }

    public function generatePdf(Request $request): Response
    {
        $this->authorize('achat.rapports.export');

        $rapport = $this->construireRapport($request);

        $pdf = Pdf::loadView('achat::statistiques.pdf', [
            'title' => $rapport['titre'],
            'columns' => $rapport['colonnes'],
            'rows' => $rapport['lignes'],
            'filters' => $this->filtresLisibles($request),
            'genereLe' => now(),
            'genrePar' => auth()->user()?->name,
        ])->setPaper('a4', 'landscape');

        return $pdf->stream(str_replace(' ', '_', mb_strtolower($rapport['titre'])).'.pdf');
    }

    /**
     * @return array{titre: string, colonnes: array<string,string>, lignes: array}
     */
    protected function construireRapport(Request $request): array
    {
        $type = $request->input('report_type', 'global_purchases');

        if (! array_key_exists($type, self::RAPPORTS)) {
            $type = 'global_purchases';
        }

        return match ($type) {
            'by_supplier_detail' => $this->rapportParFournisseur($request),
            'reliquats' => $this->rapportReliquats($request),
            'popular_articles' => $this->rapportArticlesPopulaires(),
            default => $this->rapportGlobal($request),
        };
    }

    protected function rapportGlobal(Request $request): array
    {
        $query = BonCommande::with('fournisseur')->orderByDesc('date_commande');

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->input('fournisseur_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_commande', '>=', $request->input('date_debut'));
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_commande', '<=', $request->input('date_fin'));
        }

        return [
            'titre' => self::RAPPORTS['global_purchases']['titre'],
            'colonnes' => [
                'numero_commande' => 'N° commande',
                'date_commande' => 'Date',
                'fournisseur' => 'Fournisseur',
                'montant_ht' => 'Montant HT',
                'montant_ttc' => 'Montant TTC',
                'statut' => 'Statut',
            ],
            'lignes' => $query->get()->map(fn (BonCommande $bc) => [
                'numero_commande' => $bc->numero_commande,
                'date_commande' => $bc->date_commande?->format('d/m/Y') ?? '-',
                'fournisseur' => $bc->fournisseur?->nom ?? '-',
                'montant_ht' => $this->montant($bc->montant_ht),
                'montant_ttc' => $this->montant($bc->montant_ttc),
                'statut' => $bc->statut_label,
            ])->toArray(),
        ];
    }

    protected function rapportParFournisseur(Request $request): array
    {
        $lignes = $this->statistiques->depensesParFournisseur(PHP_INT_MAX);

        if ($request->filled('fournisseur_id')) {
            $nom = Fournisseur::find($request->input('fournisseur_id'))?->nom;
            $lignes = $lignes->where('label', $nom);
        }

        return [
            'titre' => self::RAPPORTS['by_supplier_detail']['titre'],
            'colonnes' => [
                'fournisseur' => 'Fournisseur',
                'nombre_commandes' => 'Nbre commandes',
                'montant_total' => 'Montant total engagé (TTC)',
            ],
            'lignes' => $lignes->map(fn (array $ligne) => [
                'fournisseur' => $ligne['label'],
                'nombre_commandes' => $ligne['nombre'],
                'montant_total' => $this->montant($ligne['total']),
            ])->values()->toArray(),
        ];
    }

    protected function rapportReliquats(Request $request): array
    {
        $query = LigneCommande::with(['bonCommande.fournisseur', 'article'])
            ->whereHas('bonCommande', fn ($bc) => $bc->whereIn('statut', ['valide', 'partiel']))
            ->whereColumn('quantite_livree', '<', 'quantite');

        if ($request->filled('fournisseur_id')) {
            $query->whereHas(
                'bonCommande',
                fn ($bc) => $bc->where('fournisseur_id', $request->input('fournisseur_id'))
            );
        }

        return [
            'titre' => self::RAPPORTS['reliquats']['titre'],
            'colonnes' => [
                'numero_commande' => 'N° commande',
                'fournisseur' => 'Fournisseur',
                'article' => 'Article',
                'quantite_commandee' => 'Qté commandée',
                'quantite_livree' => 'Qté livrée',
                'reliquat' => 'Reliquat',
                'anciennete' => 'Ancienneté',
            ],
            'lignes' => $query->get()->map(fn (LigneCommande $ligne) => [
                'numero_commande' => $ligne->bonCommande->numero_commande,
                'fournisseur' => $ligne->bonCommande->fournisseur?->nom ?? '-',
                'article' => $ligne->article?->designation ?? '-',
                'quantite_commandee' => $ligne->quantite,
                'quantite_livree' => $ligne->quantite_livree,
                'reliquat' => $ligne->reste_a_livrer,
                'anciennete' => $ligne->bonCommande->date_commande
                    ? $ligne->bonCommande->date_commande->diffInDays(Carbon::now()).' j'
                    : '-',
            ])->toArray(),
        ];
    }

    protected function rapportArticlesPopulaires(): array
    {
        $lignes = LigneCommande::with('article')
            ->get()
            ->groupBy('article_id')
            ->map(function ($groupe) {
                $article = $groupe->first()->article;

                return [
                    'designation' => $article?->designation ?? 'Article supprimé',
                    'type' => $article?->type_label ?? '-',
                    'quantite_totale' => (int) $groupe->sum('quantite'),
                    'montant_total' => (float) $groupe->sum(fn ($ligne) => $ligne->montant_ht),
                ];
            })
            ->sortByDesc('quantite_totale')
            ->values();

        return [
            'titre' => self::RAPPORTS['popular_articles']['titre'],
            'colonnes' => [
                'designation' => 'Désignation',
                'type' => 'Type',
                'quantite_totale' => 'Quantité cumulée',
                'montant_total' => 'Montant cumulé HT',
            ],
            'lignes' => $lignes->map(fn (array $ligne) => [
                'designation' => $ligne['designation'],
                'type' => $ligne['type'],
                'quantite_totale' => $ligne['quantite_totale'],
                'montant_total' => $this->montant($ligne['montant_total']),
            ])->toArray(),
        ];
    }

    /** Filtres appliqués, restitués en clair dans l'en-tête du PDF. */
    protected function filtresLisibles(Request $request): array
    {
        $filtres = [];

        if ($request->filled('fournisseur_id') && $fournisseur = Fournisseur::find($request->input('fournisseur_id'))) {
            $filtres['Fournisseur'] = $fournisseur->nom;
        }

        if ($request->filled('statut')) {
            $filtres['Statut'] = config("achat.statuts_bc.{$request->input('statut')}.label", $request->input('statut'));
        }

        if ($request->filled('date_debut')) {
            $filtres['Date de début'] = Carbon::parse($request->input('date_debut'))->format('d/m/Y');
        }

        if ($request->filled('date_fin')) {
            $filtres['Date de fin'] = Carbon::parse($request->input('date_fin'))->format('d/m/Y');
        }

        return $filtres;
    }

    protected function montant(float|string|null $valeur): string
    {
        return number_format((float) $valeur, 0, ',', ' ').' FCFA';
    }
}
