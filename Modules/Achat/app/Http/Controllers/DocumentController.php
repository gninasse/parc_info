<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\Document;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\DocumentsBonCommande;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * D-09 — pièces justificatives du bon de commande (M-05, M-08, SPEC_UX A-04).
 *
 * Permissions dédiées (SFD §5) : `documents.store` pour déposer,
 * `documents.view` pour télécharger — l'URL directe du fichier n'existe pas,
 * tout passe par cette route contrôlée — `documents.delete` pour supprimer.
 */
class DocumentController extends Controller implements HasMiddleware
{
    public function __construct(
        private readonly DocumentsBonCommande $service,
        private readonly AchatParametres $parametres,
    ) {}

    public static function middleware(): array
    {
        return [
            // La liste des pièces s'affiche sur la fiche : même permission.
            new Middleware('permission:achat.bons_commande.index', only: ['index']),
            new Middleware('permission:achat.documents.store', only: ['store']),
            new Middleware('permission:achat.documents.view', only: ['telecharger']),
            new Middleware('permission:achat.documents.delete', only: ['destroy']),
        ];
    }

    /** L'onglet Documents de la fiche recharge sa liste ici. */
    public function index(int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $bon->documents()
                ->with(['createur:id,name', 'suppresseur:id,name'])
                ->orderBy('id')
                ->get()
                ->map(fn (Document $document) => $this->presenter($document, $bon)),
        ]);
    }

    /** M-05 — dépôt d'une pièce. */
    public function store(Request $request, int $id): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);

        // Un bon annulé est un document mort : son dossier est figé.
        if ($bon->statut === BonCommande::STATUT_ANNULE) {
            return response()->json([
                'success' => false,
                'message' => 'Bon annulé — son dossier documentaire est figé.',
            ], 409);
        }

        $tailleMaxKo = $this->parametres->tailleMaxPieceKo();

        $valide = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', array_keys(config('achat.types_documents')))],
            'fichier' => [
                'required',
                'file',
                'max:'.$tailleMaxKo,
                'mimes:'.implode(',', config('achat.documents.extensions')),
            ],
        ], [
            'fichier.mimes' => 'Format non accepté : joignez un PDF, une image ou un document bureautique.',
            'fichier.max' => 'Fichier trop volumineux (maximum '.$this->parametres->tailleMaxPieceMo().' Mo — paramètre de l\'établissement).',
            'type.in' => 'Type de pièce inconnu.',
        ]);

        $document = $this->service->deposer($bon, $valide['fichier'], $valide['type'], $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Pièce jointe ajoutée.',
            'data' => $this->presenter($document->load('createur:id,name'), $bon),
        ]);
    }

    /**
     * Téléchargement contrôlé : le fichier est servi en flux depuis le disque
     * privé, jamais exposé par une URL directe (IA-13).
     */
    public function telecharger(int $id, int $documentId): StreamedResponse
    {
        $bon = BonCommande::query()->findOrFail($id);
        $document = $bon->documents()->findOrFail($documentId);

        // Une pierre tombale n'a plus de fichier : 410, la ressource a existé.
        abort_if($document->est_supprime, 410, 'Cette pièce a été supprimée : '.$document->libelle_pierre_tombale);

        abort_unless(
            $document->chemin !== null && Storage::disk(DocumentsBonCommande::DISQUE)->exists($document->chemin),
            404,
            'Fichier introuvable sur le disque.'
        );

        return Storage::disk(DocumentsBonCommande::DISQUE)->download($document->chemin, $document->nom_original, [
            'Content-Type' => $document->mime,
        ]);
    }

    /**
     * Suppression : réelle avant validation du bon (SW simple), pierre
     * tombale motivée après (M-08). La frontière est l'ENGAGEMENT du bon,
     * pas le temps écoulé.
     */
    public function destroy(Request $request, int $id, int $documentId): JsonResponse
    {
        $bon = BonCommande::query()->findOrFail($id);
        $document = $bon->documents()->findOrFail($documentId);

        if ($document->est_supprime) {
            return response()->json([
                'success' => false,
                'message' => 'Cette pièce est déjà supprimée.',
            ], 409);
        }

        if (! $bon->estEngage()) {
            $this->service->supprimer($document, $request->user());

            return response()->json(['success' => true, 'message' => 'Pièce supprimée.']);
        }

        // M-08 : le motif est obligatoire après validation.
        $valide = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motif.required' => 'Indiquez le motif : une pièce d\'un bon validé ne disparaît pas sans explication.',
            'motif.min' => 'Le motif doit être un peu plus explicite (5 caractères au minimum).',
        ]);

        $document = $this->service->poserPierreTombale($document, $request->user(), $valide['motif']);

        return response()->json([
            'success' => true,
            'message' => 'Pièce supprimée — la trace reste au dossier.',
            'data' => $this->presenter($document->load('suppresseur:id,name'), $bon),
        ]);
    }

    /** Une ligne de l'onglet Documents, drapeaux compris. */
    private function presenter(Document $document, BonCommande $bon): array
    {
        $utilisateur = auth()->user();

        return [
            'id' => $document->id,
            'type' => $document->type,
            'type_label' => $document->type_label,
            'nom_original' => $document->nom_original,
            'taille_lisible' => $document->taille_lisible,
            'icone' => $document->icone,
            'depose_par' => $document->createur?->name ?? '—',
            'depose_le' => $document->created_at?->format('d/m/Y H:i'),
            'est_supprime' => (bool) $document->est_supprime,
            'pierre_tombale' => $document->libelle_pierre_tombale,
            // Drapeaux serveur, comme partout : le navigateur ne déduit rien.
            'peut_telecharger' => ! $document->est_supprime && $utilisateur->can('achat.documents.view'),
            'peut_supprimer' => ! $document->est_supprime && $utilisateur->can('achat.documents.delete'),
            // M-08 seulement si le bon est engagé : l'écran sait quel dialogue ouvrir.
            'suppression_motivee' => $bon->estEngage(),
            'url_telechargement' => $document->est_supprime
                ? null
                : route('achat.bons-commande.documents.telecharger', [$bon->id, $document->id]),
        ];
    }
}
