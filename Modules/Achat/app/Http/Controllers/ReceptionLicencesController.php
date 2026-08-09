<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\Achat\Exceptions\AchatException;
use Modules\Achat\Exceptions\ReceptionLicencesException;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\ReceptionLicences;
use Modules\Achat\Services\ReceptionLicencesService;
use Modules\Catalogue\Models\Article;

/**
 * A-05 — wizard de réception des licences, et M-04 constat de service fait.
 *
 * Permission unique `achat.licences.receptionner` (SFD §5) : réceptionner
 * une licence et constater un service fait sont le même geste — solder une
 * ligne immatérielle.
 */
class ReceptionLicencesController extends Controller implements HasMiddleware
{
    public function __construct(private readonly ReceptionLicencesService $service) {}

    public static function middleware(): array
    {
        return [
            new Middleware('permission:achat.licences.receptionner'),
        ];
    }

    /**
     * Pré-écran : ce que le wizard doit savoir avant d'ouvrir (reste,
     * quantité par défaut, garde du logiciel rattaché — IA-9).
     */
    public function preparer(int $bonId, int $ligneId): JsonResponse
    {
        $ligne = $this->ligne($bonId, $ligneId);
        $article = Article::query()->find($ligne->article_id);
        $logicielManquant = $article === null || $article->logiciel_id === null;

        return response()->json([
            'ligne' => [
                'id' => $ligne->id,
                'designation' => $ligne->designation,
                'nature' => $ligne->nature,
                'reste' => $ligne->reste,
                'prix_unitaire_ht' => (float) $ligne->prix_unitaire_ht,
            ],
            // IA-9 : le blocage se dit ICI, avec sa sortie — jamais à la 25e clé.
            'peut_ouvrir' => ! $logicielManquant && $ligne->reste > 0,
            'blocage' => $logicielManquant
                ? [
                    'message' => "Impossible de réceptionner : l'article {$ligne->designation} n'a pas de logiciel rattaché.",
                    'url_correction' => $article !== null && \Illuminate\Support\Facades\Route::has('catalogue.articles.index')
                        ? route('catalogue.articles.index', ['article_id' => $article->id])
                        : null,
                ]
                : ($ligne->reste <= 0 ? ['message' => 'Cette ligne est déjà entièrement livrée.', 'url_correction' => null] : null),
            'session_en_cours' => ReceptionLicences::query()
                ->enCours()
                ->where('ligne_commande_id', $ligne->id)
                ->value('id'),
        ]);
    }

    /** Ouvre (ou reprend) la session et rend l'écran plein écran du wizard. */
    public function ouvrir(Request $request, int $bonId, int $ligneId)
    {
        $ligne = $this->ligne($bonId, $ligneId);

        $valide = $request->validate([
            'quantite' => ['nullable', 'numeric', 'gt:0'],
        ]);

        try {
            $reception = $this->service->ouvrir(
                $ligne,
                $request->user(),
                (float) ($valide['quantite'] ?? $ligne->reste)
            );
        } catch (ReceptionLicencesException $e) {
            return redirect()
                ->route('achat.bons-commande.show', $bonId)
                ->with('erreur', $e->getMessage());
        }

        return redirect()->route('achat.licences.wizard', [$bonId, $ligne->id, $reception->id]);
    }

    public function wizard(int $bonId, int $ligneId, int $receptionId)
    {
        $ligne = $this->ligne($bonId, $ligneId);
        $reception = $this->reception($ligne, $receptionId);

        return view('achat::licences.wizard', [
            'bon' => $ligne->bonCommande,
            'ligne' => $ligne,
            'reception' => $reception,
            'tampon' => $reception->tampon()->orderBy('id')->get(),
        ]);
    }

    /** Une clé saisie (ou corrigée) — appelé à CHAQUE Entrée (IA-8). */
    public function saisir(Request $request, int $bonId, int $ligneId, int $receptionId): JsonResponse
    {
        $reception = $this->reception($this->ligne($bonId, $ligneId), $receptionId);

        $valide = $request->validate([
            'cle' => ['required', 'string', 'max:255'],
            'date_activation' => ['nullable', 'date'],
            'date_expiration' => ['nullable', 'date', 'after_or_equal:date_activation'],
            'tampon_id' => ['nullable', 'integer'],
        ]);

        try {
            $tampon = $this->service->saisirCle(
                $reception,
                $valide['cle'],
                $valide['date_activation'] ?? null,
                $valide['date_expiration'] ?? null,
                $valide['tampon_id'] ?? null,
            );
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $tampon->id,
                'cle' => $tampon->cle,
                'date_activation' => $tampon->date_activation?->toDateString(),
                'date_expiration' => $tampon->date_expiration?->toDateString(),
                'saisies' => $reception->tampon()->count(),
                'manquantes' => $reception->fresh()->manquantes,
            ],
        ]);
    }

    public function supprimerCle(int $bonId, int $ligneId, int $receptionId, int $tamponId): JsonResponse
    {
        $reception = $this->reception($this->ligne($bonId, $ligneId), $receptionId);
        $this->service->supprimerCle($reception, $tamponId);

        return response()->json([
            'success' => true,
            'data' => [
                'saisies' => $reception->tampon()->count(),
                'manquantes' => $reception->fresh()->manquantes,
            ],
        ]);
    }

    /** Collage / CSV avec rapport (acceptées / doublons / vides). */
    public function importer(Request $request, int $bonId, int $ligneId, int $receptionId): JsonResponse
    {
        $reception = $this->reception($this->ligne($bonId, $ligneId), $receptionId);

        $valide = $request->validate([
            'texte' => ['required', 'string'],
            'date_activation' => ['nullable', 'date'],
        ]);

        $rapport = $this->service->importerEnMasse(
            $reception,
            $valide['texte'],
            $valide['date_activation'] ?? null
        );

        return response()->json([
            'success' => true,
            'rapport' => $rapport,
            'data' => [
                'lignes' => $reception->tampon()->orderBy('id')->get(['id', 'cle', 'date_activation', 'date_expiration']),
                'saisies' => $reception->tampon()->count(),
                'manquantes' => $reception->fresh()->manquantes,
            ],
        ]);
    }

    /** SW-03 — finalisation : N licences ParcInfo en UNE transaction (IA-7). */
    public function finaliser(Request $request, int $bonId, int $ligneId, int $receptionId): JsonResponse
    {
        $reception = $this->reception($this->ligne($bonId, $ligneId), $receptionId);

        try {
            $resultat = $this->service->finaliser($reception, $request->user());
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => "{$resultat['licences']} licence(s) créée(s) dans le Parc Informatique.",
            'data' => [
                'licences' => $resultat['licences'],
                'redirection' => route('achat.bons-commande.show', $bonId),
            ],
        ]);
    }

    /** SW-05 — retour en arrière : session ABANDONNÉE, tampon vidé, tracé. */
    public function abandonner(Request $request, int $bonId, int $ligneId, int $receptionId): JsonResponse
    {
        $reception = $this->reception($this->ligne($bonId, $ligneId), $receptionId);

        try {
            $this->service->abandonner($reception, $request->user());
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Saisie abandonnée — aucune licence n\'a été créée.',
            'data' => ['redirection' => route('achat.bons-commande.show', $bonId)],
        ]);
    }

    /** M-04 — constat de service fait sur une ligne de prestation. */
    public function serviceFait(Request $request, int $bonId, int $ligneId): JsonResponse
    {
        $ligne = $this->ligne($bonId, $ligneId);

        $valide = $request->validate([
            'date' => ['required', 'date'],
            'commentaire' => ['nullable', 'string', 'max:2000'],
        ], [
            'date.required' => 'Indiquez la date du service fait.',
        ]);

        try {
            $this->service->constaterServiceFait(
                $ligne,
                $request->user(),
                $valide['date'],
                $valide['commentaire'] ?? null
            );
        } catch (AchatException $e) {
            return $this->refus($e);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service fait constaté — la ligne est soldée.',
            'data' => ['redirection' => route('achat.bons-commande.show', $bonId)],
        ]);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    /** La ligne DANS son bon : une ligne d'un autre bon est un 404. */
    private function ligne(int $bonId, int $ligneId): LigneCommande
    {
        return LigneCommande::query()
            ->with('bonCommande')
            ->where('bon_commande_id', $bonId)
            ->findOrFail($ligneId);
    }

    /** La session DANS sa ligne : idem, pas de session empruntée. */
    private function reception(LigneCommande $ligne, int $receptionId): ReceptionLicences
    {
        return ReceptionLicences::query()
            ->where('ligne_commande_id', $ligne->id)
            ->findOrFail($receptionId);
    }

    private function refus(AchatException $e): JsonResponse
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $e->getMessage(),
            'url_correction' => $e instanceof ReceptionLicencesException && $e->articleId !== null
                ? route('catalogue.articles.index', ['article_id' => $e->articleId])
                : null,
        ], fn ($valeur) => $valeur !== null), $e->status());
    }
}
