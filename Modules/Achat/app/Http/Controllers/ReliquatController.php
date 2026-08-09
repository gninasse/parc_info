<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\ReliquatsService;
use Modules\Catalogue\Models\Fournisseur;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * A-06 — écran des reliquats (D-14).
 *
 * Lecture seule : la seule action est la clôture, qui vit sur le bon
 * (BonCommandeController::cloturer) — un reliquat ne se « supprime » pas, on
 * renonce explicitement à ce qui reste, motif à l'appui.
 */
class ReliquatController extends Controller implements HasMiddleware
{
    /** Colonnes triables — liste blanche : `sort` vient du navigateur. */
    private const TRIS_AUTORISES = [
        'numero' => 'achat_bons_commande.numero',
        'fournisseur' => 'catalogue_fournisseurs.raison_sociale',
        'article' => 'achat_lignes_commande.designation',
        'reste' => 'reste_calcule',
        'age_jours' => 'achat_bons_commande.valide_le',
    ];

    public function __construct(
        private readonly ReliquatsService $reliquats,
        private readonly AchatParametres $parametres,
    ) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.reliquats.index'),
        ];
    }

    public function index()
    {
        return view('achat::reliquats.index', [
            'fournisseurs' => Fournisseur::query()->orderBy('raison_sociale')->get(['id', 'raison_sociale']),
            // Le seuil d'alerte est un paramètre (A-08) : la pilule « > N j »
            // porte la valeur de l'établissement, pas une constante.
            'seuilAlerte' => $this->parametres->delaiAlerteReliquatJours(),
        ]);
    }

    /**
     * Charge utile du tableau : lignes, total du filtre, et l'engagé non
     * livré — le chiffre du pied de page.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = $this->reliquats->requete($request);
        $seuil = $this->parametres->delaiAlerteReliquatJours();

        $total = (clone $query)->count();
        $engage = $this->reliquats->engageNonLivre($query);

        $this->appliquerTri($query, $request);

        $lignes = $query
            ->offset(max(0, (int) $request->input('offset', 0)))
            ->limit(max(1, min(100, (int) $request->input('limit', 25))))
            ->get()
            ->map(fn (LigneCommande $ligne) => $this->reliquats->presenter($ligne, $seuil));

        return response()->json([
            'total' => $total,
            'rows' => $lignes,
            'engage_non_livre_ttc' => $engage,
            'seuil_alerte_jours' => $seuil,
        ]);
    }

    /** Export du jeu FILTRÉ (pas de la page) — CSV séparé par points-virgules. */
    public function export(Request $request): StreamedResponse
    {
        $seuil = $this->parametres->delaiAlerteReliquatJours();
        $query = $this->reliquats->requete($request);

        $lignes = (clone $query)
            ->orderBy('achat_bons_commande.valide_le')
            ->get()
            ->map(fn (LigneCommande $ligne) => $this->reliquats->presenter($ligne, $seuil));

        $engage = $this->reliquats->engageNonLivre($query);
        $horodatage = now()->format('Ymd-His');

        return response()->streamDownload(function () use ($lignes, $engage, $request, $seuil) {
            $sortie = fopen('php://output', 'w');
            fwrite($sortie, "\u{FEFF}"); // BOM UTF-8 pour Excel

            fputcsv($sortie, ['Reliquats — exporté le '.now()->format('d/m/Y H:i')], ';');

            foreach ($this->libellesFiltres($request, $seuil) as $libelle => $valeur) {
                fputcsv($sortie, [$libelle.' : '.$valeur], ';');
            }

            fputcsv($sortie, [], ';');
            fputcsv($sortie, [
                'Bon de commande', 'Statut', 'Fournisseur', 'Article',
                'Commandée', 'Livrée', 'Reste', 'Montant restant HT (FCFA)',
                'Âge (jours)', 'Dernière réception',
            ], ';');

            foreach ($lignes as $ligne) {
                fputcsv($sortie, [
                    $ligne['numero'],
                    $ligne['statut'],
                    $ligne['fournisseur'],
                    $ligne['article'],
                    $ligne['quantite'],
                    $ligne['quantite_livree'],
                    $ligne['reste'],
                    $ligne['montant_reste_ht'],
                    $ligne['age_jours'] ?? '',
                    $ligne['derniere_reception'] ?? '',
                ], ';');
            }

            fputcsv($sortie, [], ';');
            fputcsv($sortie, ['Engagé non livré (FCFA TTC)', $engage], ';');

            fclose($sortie);
        }, "reliquats-{$horodatage}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    private function appliquerTri($query, Request $request): void
    {
        $colonne = self::TRIS_AUTORISES[$request->input('sort')] ?? 'achat_bons_commande.valide_le';
        $sens = $request->input('order') === 'desc' ? 'desc' : 'asc';

        // Le reste n'est pas une colonne : il se trie sur son expression.
        if ($colonne === 'reste_calcule') {
            $query->orderByRaw('(achat_lignes_commande.quantite - achat_lignes_commande.quantite_livree) '.$sens);

            return;
        }

        $query->orderBy($colonne, $sens);
    }

    /**
     * Les filtres actifs, en clair, pour l'en-tête de l'export : un tableau
     * sorti de son contexte ne veut plus rien dire.
     *
     * @return array<string, string>
     */
    private function libellesFiltres(Request $request, int $seuil): array
    {
        $filtres = [];

        if ($request->filled('fournisseur_id')) {
            $filtres['Fournisseur'] = Fournisseur::query()
                ->whereKey((int) $request->input('fournisseur_id'))
                ->value('raison_sociale') ?? '—';
        }

        if ($request->filled('age_min')) {
            $filtres['Ancienneté'] = 'plus de '.$request->input('age_min').' jours';
        }

        if ($request->filled('search')) {
            $filtres['Recherche'] = $request->input('search');
        }

        $filtres['Seuil d\'alerte de l\'établissement'] = $seuil.' jours';

        return $filtres;
    }
}
