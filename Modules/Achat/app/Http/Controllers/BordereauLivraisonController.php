<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Requests\StoreBordereauLivraisonRequest;
use Modules\Achat\Http\Requests\UpdateBordereauLivraisonRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\WizardData;
use Modules\Achat\Services\BordereauLivraisonService;
use Modules\Achat\Services\WizardValidationService;

class BordereauLivraisonController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected BordereauLivraisonService $blService,
        protected WizardValidationService $wizardService
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('achat.bordereaux.view');

        if ($request->ajax() || $request->wantsJson()) {
            $query = BordereauLivraison::with('bonCommande')
                ->latest('date_livraison');

            // Filtres
            if ($request->filled('bon_de_commande_id')) {
                $query->where('bon_de_commande_id', $request->input('bon_de_commande_id'));
            }
            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('numero_livraison', 'like', "%{$search}%")
                        ->orWhere('ref_bordereau_physique', 'like', "%{$search}%")
                        ->orWhereHas('bonCommande', function ($bcQuery) use ($search) {
                            $bcQuery->where('numero_commande', 'like', "%{$search}%");
                        });
                });
            }

            // Pagination
            $limit = $request->input('limit', 10);
            $offset = $request->input('offset', 0);

            $total = $query->count();

            $rows = $query->limit($limit)
                ->offset($offset)
                ->get()
                ->map(function ($bl) {
                    return [
                        'id' => $bl->id,
                        'numero_livraison' => $bl->numero_livraison,
                        'numero_commande' => $bl->bonCommande->numero_commande,
                        'ref_bordereau_physique' => $bl->ref_bordereau_physique,
                        'date_livraison' => $bl->date_livraison->toDateString(),
                        'statut' => $bl->statut,
                        'statut_label' => match ($bl->statut) {
                            'brouillon' => 'Brouillon',
                            'wizard' => 'En cours d\'intégration',
                            'valide' => 'Validé & Intégré',
                            default => $bl->statut
                        },
                        'created_at' => $bl->created_at->toDateTimeString(),
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        }

        $bonsCommande = BonCommande::whereIn('statut', ['valide', 'partiel'])
            ->orderBy('numero_commande')
            ->get();

        return view('achat::bordereaux.index', compact('bonsCommande'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $this->authorize('achat.bordereaux.create');

        $selectedBcId = $request->input('bon_de_commande_id');

        $bonsCommande = BonCommande::whereIn('statut', ['valide', 'partiel'])
            ->orderBy('numero_commande')
            ->get();

        return view('achat::bordereaux.create', compact('bonsCommande', 'selectedBcId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBordereauLivraisonRequest $request): JsonResponse
    {
        try {
            $bl = $this->blService->creer(
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return response()->json([
                'success' => true,
                'message' => "Le bordereau de livraison '{$bl->numero_livraison}' a été créé avec succès.",
                'redirect' => route('achat.bordereaux.show', $bl->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(BordereauLivraison $bordereaux)
    {
        // Remarque : Le paramètre s'appelle $bordereaux en raison du pluriel utilisé dans la ressource
        $this->authorize('achat.bordereaux.view');

        $bl = $bordereaux;
        $bl->load(['bonCommande.lignesCommande', 'lignesLivraison.article', 'createur']);

        // Charger tous les bons de commande valides ou partiels pour modification éventuelle
        $bonsCommande = BonCommande::whereIn('statut', ['valide', 'partiel'])
            ->orWhere('id', $bl->bon_de_commande_id)
            ->orderBy('numero_commande')
            ->get();

        $existingLines = $bl->lignesLivraison->map(function ($l) use ($bl) {
            $lc = $bl->bonCommande->lignesCommande
                ->where('article_id', $l->article_id)
                ->first();

            $maxQty = $lc ? ($lc->reste_a_livrer + $l->quantite_livree) : $l->quantite_livree;

            return [
                'id' => $l->id,
                'article_id' => $l->article_id,
                'code_article' => $l->article->code_article,
                'designation' => $l->article->designation,
                'type_label' => config("achat.types_articles.{$l->article->type_article}", $l->article->type_article),
                'quantite_commandee' => $lc ? $lc->quantite : $l->quantite_livree,
                'quantite_livree' => $l->quantite_livree,
                'max_qty' => $maxQty,
            ];
        });

        return view('achat::bordereaux.show', compact('bl', 'bonsCommande', 'existingLines'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBordereauLivraisonRequest $request, BordereauLivraison $bordereaux): JsonResponse
    {
        try {
            $this->blService->modifier(
                $bordereaux,
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return response()->json([
                'success' => true,
                'message' => "Le bordereau '{$bordereaux->numero_livraison}' a été modifié avec succès.",
                'redirect' => route('achat.bordereaux.show', $bordereaux->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(BordereauLivraison $bordereaux): JsonResponse
    {
        $this->authorize('achat.bordereaux.delete');

        try {
            if (! $bordereaux->estModifiable()) {
                throw new Exception("Ce bordereau de livraison ne peut pas être supprimé car il n'est plus en statut brouillon.");
            }

            $bordereaux->delete();

            return response()->json([
                'success' => true,
                'message' => 'Le bordereau de livraison a été supprimé avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Charge les lignes d'un bon de commande avec leur quantité restante à livrer.
     */
    public function getLignesALivrer(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bordereaux.view');

        $lignes = $bonCommande->lignesCommande()
            ->with('article')
            ->get()
            ->map(function ($ligne) {
                return [
                    'article_id' => $ligne->article_id,
                    'code_article' => $ligne->article->code_article,
                    'designation' => $ligne->article->designation,
                    'type_article' => $ligne->article->type_article,
                    'type_label' => config("achat.types_articles.{$ligne->article->type_article}", $ligne->article->type_article),
                    'quantite_commandee' => $ligne->quantite,
                    'quantite_livree' => $ligne->quantite_livree,
                    'reste_a_livrer' => $ligne->reste_a_livrer,
                    'prix_unitaire' => $ligne->prix_unitaire,
                ];
            });

        return response()->json([
            'success' => true,
            'lignes' => $lignes,
        ]);
    }

    // ── GESTION DU WIZARD ──

    /**
     * Affiche l'assistant de validation/intégration.
     */
    public function wizard(BordereauLivraison $bordereau)
    {
        $this->authorize('achat.bordereaux.edit');

        if (! $bordereau->peutLancerWizard()) {
            return redirect()->route('achat.bordereaux.show', $bordereau->id)
                ->with('error', "L'assistant ne peut pas être lancé pour ce bordereau.");
        }

        // Mettre à jour le statut à 'wizard' si brouillon
        if ($bordereau->statut === 'brouillon') {
            $bordereau->update(['statut' => 'wizard']);
        }

        $bordereau->load(['lignesLivraison.article', 'bonCommande']);

        // Filtrer les articles qui nécessitent le wizard (équipements & licences)
        $lignesWizard = $bordereau->lignesLivraison->filter(function ($ligne) {
            return in_array($ligne->article->type_article, ['equipement', 'licence']);
        });

        if ($lignesWizard->isEmpty()) {
            // Aucun équipement ou licence : la livraison ne contient que des consommables
            // On peut valider directement !
            try {
                $this->wizardService->validerBordereau($bordereau, auth()->id());

                return redirect()->route('achat.bordereaux.show', $bordereau->id)
                    ->with('success', 'Le bordereau ne contenant que des consommables, il a été validé et intégré directement.');
            } catch (Exception $e) {
                return redirect()->route('achat.bordereaux.show', $bordereau->id)
                    ->with('error', 'Erreur lors de la validation : '.$e->getMessage());
            }
        }

        // Charger les données du wizard existantes
        $wizardData = WizardData::where('bordereau_livraison_id', $bordereau->id)->get()->keyBy('article_id');

        return view('achat::bordereaux.wizard', compact('bordereau', 'lignesWizard', 'wizardData'));
    }

    /**
     * Sauvegarde temporairement une étape du wizard.
     */
    public function sauvegarderWizardEtape(Request $request, BordereauLivraison $bordereau, Article $article): JsonResponse
    {
        $this->authorize('achat.bordereaux.edit');

        try {
            $unites = $request->input('unites', []);
            $completed = $request->boolean('completed', false);

            $this->wizardService->sauvegarderEtape(
                $bordereau,
                $article,
                $unites,
                null,
                $completed
            );

            return response()->json([
                'success' => true,
                'message' => 'Étape sauvegardée avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Finalise le wizard, valide le BL et intègre tout dans le parc.
     */
    public function validerBordereau(BordereauLivraison $bordereau): JsonResponse
    {
        $this->authorize('achat.bordereaux.edit');

        try {
            $result = $this->wizardService->validerBordereau($bordereau, auth()->id());

            $countEquipements = count($result['equipements'] ?? []);
            $countLicences = count($result['licences'] ?? []);

            return response()->json([
                'success' => true,
                'message' => "Bordereau validé avec succès ! Intégration effectuée : {$countEquipements} équipement(s) créé(s) et {$countLicences} licence(s) créée(s).",
                'redirect' => route('achat.bordereaux.show', $bordereau->id),
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }
}
