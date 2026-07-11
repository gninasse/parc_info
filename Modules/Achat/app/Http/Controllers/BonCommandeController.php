<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Requests\StoreBonCommandeRequest;
use Modules\Achat\Http\Requests\UpdateBonCommandeRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\BonCommandeService;
use Modules\ParcInfo\Models\Fournisseur;

class BonCommandeController extends Controller
{
    use AuthorizesRequests;

    public function __construct(protected BonCommandeService $bonCommandeService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('achat.bons_commande.view');

        if ($request->ajax() || $request->wantsJson()) {
            $query = BonCommande::with('fournisseur')
                ->latest('date_commande');

            // Filtres
            if ($request->filled('fournisseur_id')) {
                $query->where('fournisseur_id', $request->input('fournisseur_id'));
            }
            if ($request->filled('statut')) {
                $query->where('statut', $request->input('statut'));
            }
            if ($request->filled('search')) {
                $search = $request->input('search');
                $query->where(function ($q) use ($search) {
                    $q->where('numero_commande', 'like', "%{$search}%")
                        ->orWhere('commentaire', 'like', "%{$search}%")
                        ->orWhereHas('fournisseur', function ($fQuery) use ($search) {
                            $fQuery->where('nom', 'like', "%{$search}%");
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
                ->map(function ($bc) {
                    return [
                        'id' => $bc->id,
                        'numero_commande' => $bc->numero_commande,
                        'fournisseur' => $bc->fournisseur->nom,
                        'date_commande' => $bc->date_commande->toDateString(),
                        'statut' => $bc->statut,
                        'statut_label' => match ($bc->statut) {
                            'brouillon' => 'Brouillon',
                            'valide' => 'Validé',
                            'partiel' => 'Livré Partiel',
                            'livre' => 'Livré Complet',
                            'annule' => 'Annulé',
                            default => $bc->statut
                        },
                        'montant_total' => $bc->montant_total,
                        'created_at' => $bc->created_at->toDateTimeString(),
                    ];
                });

            return response()->json([
                'total' => $total,
                'rows' => $rows,
            ]);
        }

        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();

        return view('achat::bons_commande.index', compact('fournisseurs'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('achat.bons_commande.create');

        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        // Charger uniquement les articles actifs
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        $articlesCatalogue = $articles->map(function ($a) {
            return [
                'id' => $a->id,
                'designation' => $a->designation,
                'code_article' => $a->code_article,
                'prix_indicatif' => $a->prix_indicatif,
                'type_label' => config("achat.types_articles.{$a->type_article}", $a->type_article),
            ];
        });

        return view('achat::bons_commande.create', compact('fournisseurs', 'articles', 'articlesCatalogue'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(BonCommande $bonCommande)
    {
        $this->authorize('achat.bons_commande.edit');

        if (! $bonCommande->estModifiable()) {
            return redirect()->route('achat.bons-commande.show', $bonCommande->id)
                ->with('error', "Ce bon de commande ne peut pas être modifié car il n'est plus en statut brouillon.");
        }

        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        $existingLines = $bonCommande->lignesCommande->map(function ($l) {
            return [
                'id' => $l->id,
                'article_id' => $l->article_id,
                'code_article' => $l->article->code_article,
                'designation' => $l->article->designation,
                'quantite' => $l->quantite,
                'prix_unitaire' => $l->prix_unitaire,
            ];
        });

        $articlesCatalogue = $articles->map(function ($a) {
            return [
                'id' => $a->id,
                'designation' => $a->designation,
                'code_article' => $a->code_article,
                'prix_indicatif' => $a->prix_indicatif,
                'type_label' => config("achat.types_articles.{$a->type_article}", $a->type_article),
            ];
        });

        return view('achat::bons_commande.edit', compact('bonCommande', 'fournisseurs', 'articles', 'existingLines', 'articlesCatalogue'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreBonCommandeRequest $request): JsonResponse
    {
        try {
            $bc = $this->bonCommandeService->creer(
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return response()->json([
                'success' => true,
                'message' => "Le bon de commande '{$bc->numero_commande}' a été créé avec succès.",
                'redirect' => route('achat.bons-commande.show', $bc->id),
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
    public function show(BonCommande $bonCommande)
    {
        $this->authorize('achat.bons_commande.view');

        $bonCommande->load(['fournisseur', 'lignesCommande.article', 'validateur', 'bordereauxLivraison.lignesLivraison', 'creator', 'updater', 'documents.createur']);
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $articles = Article::where('actif', true)->orderBy('designation')->get();

        $existingLines = $bonCommande->lignesCommande->map(function ($l) {
            return [
                'id' => $l->id,
                'article_id' => $l->article_id,
                'quantite' => $l->quantite,
                'quantite_livree' => $l->quantite_livree,
                'prix_unitaire' => $l->prix_unitaire,
            ];
        });

        $articlesCatalogue = $articles->map(function ($a) {
            return [
                'id' => $a->id,
                'designation' => $a->designation,
                'code_article' => $a->code_article,
                'prix_indicatif' => $a->prix_indicatif,
                'type_label' => config("achat.types_articles.{$a->type_article}", $a->type_article),
            ];
        });

        $equipementsIds = $bonCommande->bordereauxLivraison->pluck('numero_livraison');
        $equipements = \Modules\ParcInfo\Models\Equipement::whereIn('ref_bordereau', $equipementsIds)->with(['categorie', 'marque'])->get();

        return view('achat::bons_commande.show', compact('bonCommande', 'fournisseurs', 'articles', 'existingLines', 'articlesCatalogue', 'equipements'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateBonCommandeRequest $request, BonCommande $bonCommande): JsonResponse
    {
        try {
            $this->bonCommandeService->modifier(
                $bonCommande,
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return response()->json([
                'success' => true,
                'message' => "Le bon de commande '{$bonCommande->numero_commande}' a été modifié avec succès.",
                'redirect' => route('achat.bons-commande.show', $bonCommande->id),
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
    public function destroy(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.delete');

        try {
            if (! $bonCommande->estModifiable()) {
                throw new Exception("Ce bon de commande ne peut pas être supprimé car il n'est plus en statut brouillon.");
            }

            $bonCommande->delete();

            return response()->json([
                'success' => true,
                'message' => 'Le bon de commande a été supprimé avec succès.',
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validate the purchase order.
     */
    public function valider(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.edit'); // Ou une permission spécifique pour la validation

        try {
            $this->bonCommandeService->valider($bonCommande, auth()->id());

            return response()->json([
                'success' => true,
                'message' => "Le bon de commande '{$bonCommande->numero_commande}' a été validé avec succès.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cancel the purchase order.
     */
    public function annuler(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.edit');

        try {
            $this->bonCommandeService->annuler($bonCommande);

            return response()->json([
                'success' => true,
                'message' => "Le bon de commande '{$bonCommande->numero_commande}' a été annulé.",
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Print the purchase order.
     */
    public function imprimer(Request $request, BonCommande $bonCommande)
    {
        $this->authorize('achat.bons_commande.view');

        $bonCommande->load(['fournisseur', 'lignesCommande.article', 'validateur']);

        if ($request->query('pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('achat::bons_commande.print_pdf', compact('bonCommande'));
            $pdf->setPaper('a4', 'portrait');

            return $pdf->stream("bon_de_commande_{$bonCommande->numero_commande}.pdf");
        }

        return view('achat::bons_commande.imprimer', compact('bonCommande'));
    }
}
