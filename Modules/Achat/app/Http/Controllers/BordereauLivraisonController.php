<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Achat\Contracts\ParcInfoIntegrationInterface;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Http\Controllers\Concerns\ConsulteLeJournal;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Http\Requests\StoreBordereauLivraisonRequest;
use Modules\Achat\Http\Requests\UpdateBordereauLivraisonRequest;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Services\BordereauLivraisonService;
use Modules\Achat\Services\WizardValidationService;
use Symfony\Component\HttpFoundation\Response;

class BordereauLivraisonController extends Controller
{
    use AuthorizesRequests, ConsulteLeJournal, RepondEnJson;

    public function __construct(
        protected BordereauLivraisonService $bordereauService,
        protected WizardValidationService $wizardService,
        protected ParcInfoIntegrationInterface $parcInfo,
    ) {}

    /** Liste des bordereaux (E-09). */
    public function index(): View
    {
        $this->authorize('achat.bordereaux.view');

        return view('achat::bordereaux.index', [
            'bonsCommande' => BonCommande::livrables()->orderBy('numero_commande')->get(),
            'statuts' => config('achat.statuts_bl'),
        ]);
    }

    public function getData(Request $request): JsonResponse
    {
        $this->authorize('achat.bordereaux.view');

        $query = BordereauLivraison::with('bonCommande')->latest('date_livraison');

        if ($request->filled('bon_de_commande_id')) {
            $query->where('bon_de_commande_id', $request->input('bon_de_commande_id'));
        }

        if ($request->filled('statut')) {
            $query->where('statut', $request->input('statut'));
        }

        if ($request->filled('search')) {
            $recherche = '%'.$request->input('search').'%';

            $query->where(function ($sousRequete) use ($recherche) {
                $sousRequete->where('numero_livraison', 'like', $recherche)
                    ->orWhere('ref_bordereau_physique', 'like', $recherche)
                    ->orWhereHas('bonCommande', fn ($bc) => $bc->where('numero_commande', 'like', $recherche));
            });
        }

        $total = $query->count();

        $rows = $query->limit($request->integer('limit', 25))
            ->offset($request->integer('offset', 0))
            ->get()
            ->map(fn (BordereauLivraison $bl) => [
                'id' => $bl->id,
                'numero_livraison' => $bl->numero_livraison,
                'numero_commande' => $bl->bonCommande?->numero_commande ?? '-',
                'ref_bordereau_physique' => $bl->ref_bordereau_physique,
                'date_livraison' => $bl->date_livraison?->toDateString(),
                'statut' => $bl->statut,
                'statut_label' => $bl->statut_label,
                'created_at' => $bl->created_at?->toDateTimeString(),
            ]);

        return $this->table($total, $rows);
    }

    /** Formulaire de réception (E-10). */
    public function create(Request $request): View
    {
        $this->authorize('achat.bordereaux.create');

        return view('achat::bordereaux.create', [
            'bonsCommande' => BonCommande::livrables()->with('fournisseur')->orderBy('numero_commande')->get(),
            'bonCommandePreselectionne' => $request->input('bon_de_commande_id'),
        ]);
    }

    /** Fiche détaillée et édition sur place (E-11). */
    public function show(BordereauLivraison $bordereau): View
    {
        $this->authorize('achat.bordereaux.view');

        $bordereau->load([
            'bonCommande.fournisseur',
            'bonCommande.lignesCommande',
            'lignesLivraison.article',
            'creator',
            'validateur',
            'documents.creator',
        ]);

        return view('achat::bordereaux.show', [
            'bordereau' => $bordereau,
            'lignesExistantes' => $this->bordereauService->lignesALivrer($bordereau->bonCommande, $bordereau),
            'equipements' => $this->parcInfo->equipementsDesBordereaux([$bordereau->numero_livraison]),
            'journal' => $this->journalDe($bordereau),
        ]);
    }

    public function store(StoreBordereauLivraisonRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $bordereau = $this->bordereauService->creer(
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return $this->succes(
                "Le bordereau de livraison {$bordereau->numero_livraison} a été enregistré.",
                ['redirect' => route('achat.bordereaux.show', $bordereau)]
            );
        });
    }

    public function update(UpdateBordereauLivraisonRequest $request, BordereauLivraison $bordereau): JsonResponse
    {
        return $this->executer(function () use ($request, $bordereau) {
            $this->bordereauService->modifier(
                $bordereau,
                $request->safe()->except('lignes'),
                $request->input('lignes')
            );

            return $this->succes("Le bordereau {$bordereau->numero_livraison} a été modifié.");
        });
    }

    public function destroy(BordereauLivraison $bordereau): JsonResponse
    {
        $this->authorize('achat.bordereaux.delete');

        return $this->executer(function () use ($bordereau) {
            $numero = $bordereau->numero_livraison;
            $this->bordereauService->supprimer($bordereau);

            return $this->succes("Le bordereau de livraison {$numero} a été supprimé.");
        });
    }

    /** Lignes livrables d'un bon de commande, pour la saisie d'une réception. */
    public function lignesALivrer(BonCommande $bonCommande): JsonResponse
    {
        $this->authorize('achat.bordereaux.view');

        return $this->donnees($this->bordereauService->lignesALivrer($bonCommande));
    }

    // ── Assistant d'intégration (E-12) ─────────────────────────────────────

    /**
     * RG-WZ-01 / RG-WZ-04 — Ouverture de l'assistant.
     *
     * Un bordereau sans équipement ni licence est intégré directement.
     */
    public function wizard(BordereauLivraison $bordereau): View|RedirectResponse
    {
        $this->authorize('achat.bordereaux.valider');

        if (! $bordereau->peutLancerWizard()) {
            return redirect()
                ->route('achat.bordereaux.show', $bordereau)
                ->with('error', "L'assistant ne peut pas être lancé : ce bordereau est déjà validé.");
        }

        $bordereau->load(['lignesLivraison.article', 'bonCommande', 'wizardData']);
        $lignesWizard = $bordereau->lignesNecessitantWizard();

        // RG-WZ-04 : uniquement des consommables ou des prestations
        if ($lignesWizard->isEmpty()) {
            try {
                $resultat = $this->wizardService->validerBordereau($bordereau, auth()->id());

                return redirect()
                    ->route('achat.bordereaux.show', $bordereau)
                    ->with('success', 'Ce bordereau ne contenant aucun équipement ni licence à inventorier, '
                        .'il a été validé et intégré directement.');
            } catch (RegleMetierException $e) {
                return redirect()
                    ->route('achat.bordereaux.show', $bordereau)
                    ->with('error', $e->getMessage());
            }
        }

        // RG-WZ-02 : le bordereau sort du brouillon, avec retour arrière possible
        if ($bordereau->statut === 'brouillon') {
            $bordereau->update(['statut' => 'wizard']);
        }

        return view('achat::bordereaux.wizard', [
            'bordereau' => $bordereau,
            'lignesWizard' => $lignesWizard,
            'saisies' => $bordereau->wizardData->keyBy('article_id'),
            'champsParCategorie' => $this->champsParCategorie($lignesWizard),
        ]);
    }

    /** RG-WZ-07 — Enregistrement d'une étape. */
    public function sauvegarderEtape(Request $request, BordereauLivraison $bordereau, Article $article): JsonResponse
    {
        $this->authorize('achat.bordereaux.valider');

        $request->validate([
            'unites' => ['required', 'array', 'min:1'],
            'attributs_communs' => ['nullable', 'array'],
        ]);

        return $this->executer(function () use ($request, $bordereau, $article) {
            $this->wizardService->sauvegarderEtape(
                $bordereau,
                $article,
                $request->input('unites', []),
                $request->input('attributs_communs'),
                $request->boolean('completed')
            );

            return $this->succes('Étape enregistrée.');
        });
    }

    /** RGC-05 — Finalisation et intégration définitive au parc. */
    public function validerBordereau(BordereauLivraison $bordereau): JsonResponse
    {
        $this->authorize('achat.bordereaux.valider');

        return $this->executer(function () use ($bordereau) {
            $resultat = $this->wizardService->validerBordereau($bordereau, auth()->id());

            $parties = [];

            if ($nb = count($resultat['equipements'])) {
                $parties[] = "{$nb} équipement(s) créé(s)";
            }

            if ($nb = count($resultat['licences'])) {
                $parties[] = "{$nb} licence(s) créée(s)";
            }

            return $this->succes(
                'Bordereau validé et intégré au parc'
                .($parties ? ' : '.implode(', ', $parties).'.' : '.'),
                ['redirect' => route('achat.bordereaux.show', $bordereau)]
            );
        });
    }

    /** EF-BL-12 — Retour au brouillon (correction AN-10). */
    public function revenirBrouillon(BordereauLivraison $bordereau): JsonResponse
    {
        $this->authorize('achat.bordereaux.edit');

        return $this->executer(function () use ($bordereau) {
            $this->bordereauService->revenirEnBrouillon($bordereau);

            return $this->succes(
                "Le bordereau {$bordereau->numero_livraison} est repassé en brouillon. "
                .'Les saisies d\'inventaire non finalisées ont été abandonnées.'
            );
        });
    }

    /** E-13 — Bordereau au format PDF. */
    public function imprimer(BordereauLivraison $bordereau): Response
    {
        $this->authorize('achat.bordereaux.view');

        $bordereau->load(['bonCommande.fournisseur', 'lignesLivraison.article', 'creator']);

        return Pdf::loadView('achat::bordereaux.print_pdf', compact('bordereau'))
            ->setPaper('a4', 'portrait')
            ->stream("bordereau_livraison_{$bordereau->numero_livraison}.pdf");
    }

    /**
     * RG-INT-05 — Champs personnalisés à saisir, par catégorie d'équipement.
     *
     * Passe par le contrat d'intégration : aucun accès direct aux modèles
     * ParcInfo depuis le contrôleur.
     */
    protected function champsParCategorie(iterable $lignesWizard): array
    {
        $champs = [];

        foreach ($lignesWizard as $ligne) {
            $categorieId = $ligne->article->categorie_equipement_id;

            if ($categorieId && ! isset($champs[$categorieId])) {
                $champs[$categorieId] = $this->parcInfo->champsDeCategorie($categorieId);
            }
        }

        return $champs;
    }
}
