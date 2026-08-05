<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\ReferencePrixService;

/**
 * API inter-modules du module Achat (API_Inter_Modules.md §4).
 *
 * Contrats consommés par le module Stock (modale de sélection de commande,
 * pré-remplissage du bon d'entrée, scan QR) et par les écrans Achat
 * eux-mêmes (référence de prix, cumul fournisseur).
 *
 * Engagements tenus ici (§1.2) : permission serveur, listes bornées, `LIKE`
 * portable uniquement, montants en chaîne décimale, dates `YYYY-MM-DD`,
 * aucune écriture — ces GET sont rejouables à l'infini.
 */
class ApiController extends Controller implements HasMiddleware
{
    private const LIMIT_DEFAUT = 50;

    private const LIMIT_MAX = 100;

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.api.view'),
        ];
    }

    /**
     * §4.1 — Bons de commande livrables, pour la modale M-02 et le scan QR.
     * Seuls VALIDE et PARTIEL : un brouillon n'engage rien, un bon clôturé
     * ou annulé n'attend plus rien.
     */
    public function bonsCommandeALivrer(Request $request): JsonResponse
    {
        $query = BonCommande::query()
            ->receptionnables()
            ->with('fournisseur:id,raison_sociale');

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', (int) $request->input('fournisseur_id'));
        }

        // Recherche par numéro OU par article de la commande (LIKE portable)
        if ($request->filled('q')) {
            $q = mb_strtolower($request->input('q'));

            $query->where(function ($sous) use ($q) {
                $sous->whereRaw('LOWER(numero) LIKE ?', ["%{$q}%"])
                    ->orWhereIn('id', LigneCommande::query()
                        ->select('bon_commande_id')
                        ->whereRaw('LOWER(designation) LIKE ?', ["%{$q}%"]));
            });
        }

        $bons = $query
            ->orderByDesc('valide_le')
            ->limit($this->limit($request))
            ->get();

        // Restes agrégés en une requête plutôt qu'une par bon
        $restes = DB::table('achat_lignes_commande')
            ->whereIn('bon_commande_id', $bons->pluck('id'))
            ->whereColumn('quantite_livree', '<', 'quantite')
            ->groupBy('bon_commande_id')
            ->selectRaw('bon_commande_id, COUNT(*) AS lignes_restantes')
            ->selectRaw('COALESCE(SUM(quantite - quantite_livree), 0) AS unites_restantes')
            ->get()
            ->keyBy('bon_commande_id');

        return response()->json([
            'data' => $bons->map(fn (BonCommande $bon) => [
                'id' => $bon->id,
                'numero' => $bon->numero,
                'statut' => $bon->statut,
                'fournisseur' => [
                    'id' => $bon->fournisseur_id,
                    'nom' => $bon->fournisseur_libelle ?? $bon->fournisseur?->raison_sociale,
                ],
                'valide_le' => $bon->valide_le?->toDateString(),
                'lignes_restantes' => (int) ($restes[$bon->id]->lignes_restantes ?? 0),
                'unites_restantes' => (float) ($restes[$bon->id]->unites_restantes ?? 0),
                'montant_ttc' => number_format((float) $bon->montant_ttc, 2, '.', ''),
            ])->all(),
        ]);
    }

    /**
     * §4.2 — Le contrat de pré-remplissage du bon d'entrée : ce qu'il reste à
     * livrer, au PRIX FIGÉ de la commande (jamais le prix indicatif courant).
     */
    public function lignesALivrer(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->with('lignes.article:id,code')->find($id);

        if ($bon === null) {
            return response()->json(['message' => "Bon de commande introuvable (#{$id})."], 404);
        }

        if (! in_array($bon->statut, BonCommande::STATUTS_RECEPTIONNABLES, true)) {
            return response()->json([
                'message' => "Ce bon n'est pas livrable (statut : {$bon->statut_label}).",
            ], 422);
        }

        $lignes = $bon->lignes
            ->filter(fn (LigneCommande $ligne) => $ligne->reste > 0)
            ->map(fn (LigneCommande $ligne) => [
                'ligne_id' => $ligne->id,
                'article_id' => $ligne->article_id,
                'code' => $ligne->article?->code,
                'designation' => $ligne->designation,
                'nature' => $ligne->nature,
                'quantite_commandee' => number_format((float) $ligne->quantite, 2, '.', ''),
                'quantite_livree' => number_format((float) $ligne->quantite_livree, 2, '.', ''),
                'reste_a_livrer' => number_format($ligne->reste, 2, '.', ''),
                'prix_unitaire_ht' => number_format((float) $ligne->prix_unitaire_ht, 2, '.', ''),
                'taux_tva' => number_format((float) $ligne->taux_tva, 2, '.', ''),
            ])
            ->values()
            ->all();

        return response()->json([
            'bon_commande' => [
                'id' => $bon->id,
                'numero' => $bon->numero,
                'statut' => $bon->statut,
                'fournisseur_id' => $bon->fournisseur_id,
            ],
            'lignes' => $lignes,
        ]);
    }

    /** §4.3 — Résolution d'un numéro scanné (QR code du PDF). */
    public function resoudre(Request $request): JsonResponse
    {
        $numero = trim((string) $request->input('numero'));

        if ($numero === '') {
            return response()->json([
                'message' => 'Numéro de bon de commande manquant.',
                'errors' => ['numero' => ['Le numéro est obligatoire.']],
            ], 422);
        }

        $bon = BonCommande::query()->where('numero', $numero)->first();

        if ($bon === null) {
            return response()->json(['message' => "Aucun bon de commande {$numero}."], 404);
        }

        return response()->json([
            'id' => $bon->id,
            'numero' => $bon->numero,
            'statut' => $bon->statut,
        ]);
    }

    /**
     * §4.4 — La référence de prix NON MANIPULABLE (A14) : le dernier prix
     * réellement payé, calculé sur les bons engagés. C'est ce qui rend un
     * écart visible même si le prix indicatif du Catalogue a été ajusté.
     *
     * Le calcul appartient à `ReferencePrixService`, qui alimente aussi le
     * popover PO-01 de l'écran de saisie et la pilule d'écart : une seule
     * définition de la référence, quel que soit l'endroit où elle s'affiche.
     */
    public function historiquePrix(int $articleId, ReferencePrixService $reference): JsonResponse
    {
        return response()->json($reference->pour($articleId));
    }

    /** §4.5 — Cumul du mois par fournisseur, pour le Swal de visa (UX2-08). */
    public function cumulMois(Request $request, int $fournisseurId): JsonResponse
    {
        $mois = $request->filled('mois')
            ? \Illuminate\Support\Carbon::parse($request->input('mois').'-01')
            : now();

        $bons = BonCommande::query()
            ->engages()
            ->horsRegularisation()
            ->where('fournisseur_id', $fournisseurId)
            ->whereBetween('date_document', [$mois->copy()->startOfMonth(), $mois->copy()->endOfMonth()])
            ->get(['montant_ttc']);

        $totalHistorique = BonCommande::query()
            ->engages()
            ->where('fournisseur_id', $fournisseurId)
            ->count();

        $fournisseur = DB::table('catalogue_fournisseurs')->where('id', $fournisseurId)->first();

        return response()->json([
            'fournisseur_id' => $fournisseurId,
            'mois' => $mois->format('Y-m'),
            'nb_bc_valides' => $bons->count(),
            'cumul_ttc' => number_format((float) $bons->sum('montant_ttc'), 2, '.', ''),
            // « Premier BC » au sens du signal UX4-03 : aucun bon engagé avant celui-ci
            'premier_bc' => $totalHistorique === 0,
            'fournisseur_cree_le' => $fournisseur?->created_at
                ? \Illuminate\Support\Carbon::parse($fournisseur->created_at)->toDateString()
                : null,
        ]);
    }

    /**
     * §4.6 — Vue consolidée des réceptions intégrées : les compteurs Achat
     * font foi (le module est source de vérité du reste à livrer).
     */
    public function receptions(int $id): JsonResponse
    {
        $bon = BonCommande::query()->find($id);

        if ($bon === null) {
            return response()->json(['message' => "Bon de commande introuvable (#{$id})."], 404);
        }

        $integrations = IntegrationReception::query()
            ->where('bon_commande_id', $bon->id)
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'bon_commande' => [
                'id' => $bon->id,
                'numero' => $bon->numero,
                'statut' => $bon->statut,
            ],
            'data' => $integrations->map(fn (IntegrationReception $integration) => [
                'sens' => $integration->sens,
                'entree_id' => $integration->entree_id,
                'mouvement_id' => $integration->mouvement_id,
                'reference' => $integration->reference,
                'integre_le' => $integration->created_at?->toIso8601String(),
                'lignes' => $integration->detail ?? [],
            ])->all(),
        ]);
    }

    private function limit(Request $request): int
    {
        $limit = (int) $request->input('limit', self::LIMIT_DEFAUT);

        return max(1, min($limit, self::LIMIT_MAX));
    }
}
