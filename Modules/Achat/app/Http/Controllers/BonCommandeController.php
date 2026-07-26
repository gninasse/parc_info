<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Achat\Http\Controllers\Concerns\ConsulteLeJournal;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Http\Requests\StoreBonCommandeRequest;
use Modules\Achat\Http\Requests\UpdateBonCommandeRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\BonCommandeService;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Fournisseur;
use Symfony\Component\HttpFoundation\Response;

class BonCommandeController extends Controller
{
    use AuthorizesRequests, ConsulteLeJournal, RepondEnJson;

    public function __construct(protected BonCommandeService $bonCommandeService) {}

    /** Liste des bons de commande (E-03). */
    public function index(): View
    {
        $this->authorize('achat.bons_commande.view');

        return view('achat::bons_commande.index', [
            'fournisseurs' => Fournisseur::where('est_actif', true)->orderBy('nom')->get(),
            'statuts' => config('achat.statuts_bc'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.bons_commande.view');

        $query = BonCommande::with('fournisseur')->latest('date_commande');

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

        if ($request->filled('search')) {
            $recherche = '%'.$request->input('search').'%';

            $query->where(function ($sousRequete) use ($recherche) {
                $sousRequete->where('numero_commande', 'like', $recherche)
                    ->orWhere('commentaire', 'like', $recherche)
                    ->orWhereHas('fournisseur', fn ($f) => $f->where('nom', 'like', $recherche));
            });
        }

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (BonCommande $bc) => [
                'id' => $bc->id,
                'numero_commande' => $bc->numero_commande,
                'fournisseur' => $bc->fournisseur?->nom ?? '-',
                'date_commande' => $bc->date_commande?->toDateString(),
                'statut' => $bc->statut,
                'statut_label' => $bc->statut_label,
                'montant_ht' => (float) $bc->montant_ht,
                'montant_ttc' => (float) $bc->montant_ttc,
                'created_at' => $bc->created_at?->toDateTimeString(),
            ]);

        return $this->table($total, $rows);
    }

    /** Formulaire de création (E-04). */
    public function create(): View
    {
        $this->authorize('achat.bons_commande.create');

        return view('achat::bons_commande.create', [
            'fournisseurs' => Fournisseur::where('est_actif', true)->orderBy('nom')->get(),
            'articlesCatalogue' => $this->catalogueArticles(),
            'bonCommande' => null,
            'lignesExistantes' => [],
        ]);
    }

    /** Formulaire de modification (E-05). */
    public function edit(BonCommande $bonCommande): View|RedirectResponse
    {
        $this->authorize('achat.bons_commande.edit');

        // RG-BC-03
        if (! $bonCommande->estModifiable()) {
            return redirect()
                ->route('achat.bons-commande.show', $bonCommande)
                ->with('error', "Ce bon de commande n'est plus modifiable : il n'est plus en statut brouillon.");
        }

        $bonCommande->load('lignesCommande.article');

        return view('achat::bons_commande.edit', [
            'fournisseurs' => Fournisseur::where('est_actif', true)->orderBy('nom')->get(),
            'articlesCatalogue' => $this->catalogueArticles(),
            'bonCommande' => $bonCommande,
            'lignesExistantes' => $bonCommande->lignesCommande->map(fn ($ligne) => [
                'article_id' => $ligne->article_id,
                'code_article' => $ligne->article->code_article,
                'designation' => $ligne->article->designation,
                'quantite' => $ligne->quantite,
                'prix_unitaire' => (float) $ligne->prix_unitaire,
                'taux_tva' => (float) $ligne->taux_tva,
            ])->values(),
        ]);
    }

    /** Fiche détaillée (E-06). */
    public function show(BonCommande $bonCommande): View
    {
        $this->authorize('achat.bons_commande.view');

        $bonCommande->load([
            'fournisseur',
            'lignesCommande.article',
            'validateur',
            'annulateur',
            'bordereauxLivraison.lignesLivraison',
            'creator',
            'updater',
            'documents.creator',
        ]);

        $numerosBordereaux = $bonCommande->bordereauxLivraison->pluck('numero_livraison');

        return view('achat::bons_commande.show', [
            'bonCommande' => $bonCommande,
            'equipements' => Equipement::whereIn('ref_bordereau', $numerosBordereaux)
                ->with(['categorie', 'marque'])
                ->get(),
            'journal' => $this->journalDe($bonCommande),
        ]);
    }

    public function store(StoreBonCommandeRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $bonCommande = $this->bonCommandeService->creer(
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return $this->succes(
                "Le bon de commande {$bonCommande->numero_commande} a été créé.",
                ['redirect' => route('achat.bons-commande.show', $bonCommande)]
            );
        });
    }

    public function update(UpdateBonCommandeRequest $request, BonCommande $bonCommande): JsonResponse
    {
        return $this->executer(function () use ($request, $bonCommande) {
            $this->bonCommandeService->modifier(
                $bonCommande,
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return $this->succes(
                "Le bon de commande {$bonCommande->numero_commande} a été modifié.",
                ['redirect' => route('achat.bons-commande.show', $bonCommande)]
            );
        });
    }

    public function destroy(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.delete');

        return $this->executer(function () use ($bonCommande) {
            $numero = $bonCommande->numero_commande;
            $this->bonCommandeService->supprimer($bonCommande);

            return $this->succes("Le bon de commande {$numero} a été supprimé.");
        });
    }

    /** RG-BC-04 — Permission dédiée (correction AN-02). */
    public function valider(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.valider');

        return $this->executer(function () use ($bonCommande) {
            $this->bonCommandeService->valider($bonCommande, auth()->id());

            return $this->succes("Le bon de commande {$bonCommande->numero_commande} a été validé.");
        });
    }

    /** RG-BC-05 — Permission dédiée, motif obligatoire. */
    public function annuler(Request $request, BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.annuler');

        $request->validate(
            ['motif' => ['required', 'string', 'min:5', 'max:1000']],
            ['motif.required' => "Le motif d'annulation est obligatoire.",
                'motif.min' => "Le motif d'annulation doit être explicite (5 caractères minimum)."]
        );

        return $this->executer(function () use ($request, $bonCommande) {
            $this->bonCommandeService->annuler($bonCommande, auth()->id(), $request->input('motif'));

            return $this->succes("Le bon de commande {$bonCommande->numero_commande} a été annulé.");
        });
    }

    /** EF-BC-18 — Clôture du reliquat d'une commande abandonnée. */
    public function cloturer(Request $request, BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bons_commande.cloturer');

        $request->validate(
            ['motif' => ['required', 'string', 'min:5', 'max:1000']],
            ['motif.required' => 'Le motif de clôture est obligatoire.']
        );

        return $this->executer(function () use ($request, $bonCommande) {
            $reste = $bonCommande->reste_a_livrer;

            $this->bonCommandeService->cloturerReliquat($bonCommande, auth()->id(), $request->input('motif'));

            return $this->succes(
                "Le reliquat du bon de commande {$bonCommande->numero_commande} a été clôturé ".
                "({$reste} unité(s) non livrée(s))."
            );
        });
    }

    /** E-07 / E-08 — Aperçu HTML, ou PDF si ?pdf=1. */
    public function imprimer(Request $request, BonCommande $bonCommande): View|Response
    {
        $this->authorize('achat.bons_commande.view');

        $bonCommande->load(['fournisseur', 'lignesCommande.article', 'validateur']);

        if ($request->boolean('pdf')) {
            return Pdf::loadView('achat::bons_commande.print_pdf', compact('bonCommande'))
                ->setPaper('a4', 'portrait')
                ->stream("bon_de_commande_{$bonCommande->numero_commande}.pdf");
        }

        return view('achat::bons_commande.imprimer', compact('bonCommande'));
    }

    /**
     * Catalogue injecté dans les formulaires pour la sélection d'articles.
     * RG-ART-09 : seuls les articles actifs sont commandables.
     */
    protected function catalogueArticles(): array
    {
        return Article::actifs()
            ->orderBy('designation')
            ->get()
            ->map(fn (Article $article) => [
                'id' => $article->id,
                'code_article' => $article->code_article,
                'designation' => $article->designation,
                'type_article' => $article->type_article,
                'type_label' => $article->type_label,
                'prix_indicatif' => (float) $article->prix_indicatif,
                'taux_tva' => (float) $article->taux_tva,
                'unite_mesure' => $article->unite_mesure,
            ])
            ->values()
            ->toArray();
    }
}
