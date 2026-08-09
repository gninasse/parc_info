<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Stock\Models\Document;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Models\Transfert;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pièces jointes des bons (diligence 5) : BL scanné, photo du colis,
 * courrier de réclamation… Un seul contrôleur pour les trois types de
 * documents, adressés par {type}/{id}.
 *
 * Permissions : lecture avec l'`index` du type, ajout et suppression avec
 * son `store` — un bon validé reste consultable mais ne reçoit plus de
 * pièces (le journal est figé, ses annexes aussi).
 */
class DocumentController extends Controller implements HasMiddleware
{
    /** Type d'URL → modèle. */
    private const TYPES = [
        'entrees' => Entree::class,
        'sorties' => Sortie::class,
        'transferts' => Transfert::class,
    ];

    public static function middleware(): array
    {
        return [
            new Middleware(
                'permission:stock.entrees.index|stock.sorties.index|stock.transferts.index',
                only: ['index', 'download']
            ),
            new Middleware(
                'permission:stock.entrees.store|stock.sorties.store|stock.transferts.store',
                only: ['store', 'destroy']
            ),
        ];
    }

    public function index(string $type, int $id): JsonResponse
    {
        $bon = $this->bon($type, $id);
        $this->autoriserType($type, 'index');

        return response()->json([
            'success' => true,
            // Les pierres tombales sont RENDUES avec les autres : c'est leur
            // raison d'être (une pièce retirée doit rester visible).
            'data' => $bon->documents()
                ->with(['createur:id,name', 'suppresseur:id,name'])
                ->orderBy('id')
                ->get()
                ->map(fn (Document $doc) => $this->presenter($doc, $type)),
        ]);
    }

    public function store(Request $request, string $type, int $id): JsonResponse
    {
        $bon = $this->bon($type, $id);
        $this->autoriserType($type, 'store');

        if ($bon->estValide()) {
            return response()->json([
                'success' => false,
                'message' => 'Bon validé — ses pièces jointes ne peuvent plus être modifiées.',
            ], 409);
        }

        $tailleMax = (int) config('stock.documents.taille_max_ko', 5120);

        $valide = $request->validate([
            'fichiers' => ['required', 'array', 'max:10'],
            'fichiers.*' => [
                'file',
                'max:'.$tailleMax,
                'mimes:'.implode(',', config('stock.documents.extensions', ['pdf', 'png', 'jpg'])),
            ],
            // BR-01 : la nature de la pièce. Par défaut « autre », pour ne
            // rien casser des dépôts existants.
            'type' => ['nullable', \Illuminate\Validation\Rule::in(array_keys(Document::TYPES))],
        ], [
            'fichiers.*.mimes' => 'Format non accepté : joignez un PDF, une image ou un document bureautique.',
            'fichiers.*.max' => "Fichier trop volumineux (maximum {$tailleMax} Ko).",
            'type.in' => 'Nature de pièce inconnue.',
        ]);

        $typePiece = $valide['type'] ?? Document::TYPE_AUTRE;

        $crees = DB::transaction(function () use ($bon, $valide, $type, $typePiece) {
            return collect($valide['fichiers'])->map(function ($fichier) use ($bon, $type, $typePiece) {
                $chemin = $fichier->store(Document::DOSSIER."/{$type}/{$bon->id}", Document::DISQUE);

                return $bon->documents()->create([
                    'type' => $typePiece,
                    'nom_original' => $fichier->getClientOriginalName(),
                    'chemin' => $chemin,
                    // MIME détecté SERVEUR, jamais celui déclaré par le client.
                    'mime' => $fichier->getMimeType(),
                    'taille' => $fichier->getSize(),
                    'created_by' => auth()->id(),
                ]);
            });
        });

        return response()->json([
            'success' => true,
            'message' => $crees->count().' pièce(s) jointe(s) ajoutée(s).',
            'data' => $crees->map(fn (Document $doc) => $this->presenter($doc->load('createur:id,name'), $type)),
        ]);
    }

    /** Téléchargement contrôlé : le fichier n'est jamais exposé en direct. */
    public function download(string $type, int $id, int $document): StreamedResponse
    {
        $bon = $this->bon($type, $id);
        $this->autoriserType($type, 'index');

        $piece = $bon->documents()->findOrFail($document);

        // Une pierre tombale n'a plus de fichier : 410, la pièce a existé.
        abort_if($piece->est_supprime, 410, 'Cette pièce a été supprimée : '.$piece->libelle_pierre_tombale);

        abort_unless(
            $piece->chemin !== null && Storage::disk(Document::DISQUE)->exists($piece->chemin),
            404,
            'Fichier introuvable sur le disque.'
        );

        return Storage::disk(Document::DISQUE)->download($piece->chemin, $piece->nom_original, [
            'Content-Type' => $piece->mime,
        ]);
    }

    /**
     * Suppression d'une pièce.
     *
     * AVANT validation : réelle (fichier + ligne) — un brouillon n'engage
     * rien. APRÈS validation : PIERRE TOMBALE motivée (BR-01, même doctrine
     * qu'Achat A16) — une pièce gênante ne disparaît pas d'un document
     * engagé sans laisser de trace.
     */
    public function destroy(Request $request, string $type, int $id, int $document): JsonResponse
    {
        $bon = $this->bon($type, $id);
        $this->autoriserType($type, 'store');

        $piece = $bon->documents()->findOrFail($document);

        if ($piece->est_supprime) {
            return response()->json([
                'success' => false,
                'message' => 'Cette pièce est déjà supprimée.',
            ], 409);
        }

        if (! $bon->estValide()) {
            $piece->delete(); // le fichier suit

            return response()->json(['success' => true, 'message' => 'Pièce jointe supprimée.']);
        }

        $valide = $request->validate([
            'motif' => ['required', 'string', 'min:5', 'max:2000'],
        ], [
            'motif.required' => 'Indiquez le motif : une pièce d\'un bon validé ne disparaît pas sans explication.',
            'motif.min' => 'Le motif doit être un peu plus explicite (5 caractères au minimum).',
        ]);

        DB::transaction(function () use ($piece, $valide) {
            if ($piece->chemin !== null) {
                Storage::disk(Document::DISQUE)->delete($piece->chemin);
            }

            $piece->forceFill([
                'chemin' => null,
                'est_supprime' => true,
                'supprime_par' => auth()->id(),
                'supprime_le' => now(),
                'motif_suppression' => $valide['motif'],
            ])->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Pièce supprimée — la trace reste au dossier.',
            'data' => $this->presenter($piece->fresh()->load('suppresseur:id,name'), $type),
        ]);
    }

    // ── Privé ──────────────────────────────────────────────────────────────

    private function bon(string $type, int $id)
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::query()->findOrFail($id);
    }

    /** La pièce jointe suit les droits de SON type de bon. */
    private function autoriserType(string $type, string $action): void
    {
        $permission = 'stock.'.$type.'.'.$action;

        abort_unless(auth()->user()?->can($permission), 403);
    }

    private function presenter(Document $document, string $type): array
    {
        return [
            'id' => $document->id,
            'type' => $document->type,
            'type_label' => $document->type_label,
            'est_bl' => $document->estBlFournisseur(),
            'nom' => $document->nom_original,
            'mime' => $document->mime,
            'taille' => $document->taille_lisible,
            'icone' => $document->icone,
            'est_image' => $document->estImage(),
            'est_pdf' => $document->estPdf(),
            'est_supprime' => (bool) $document->est_supprime,
            'pierre_tombale' => $document->libelle_pierre_tombale,
            'ajoute_le' => $document->created_at?->format('d/m/Y H:i'),
            'ajoute_par' => $document->createur?->name,
            'url' => route('stock.documents.download', [$type, $document->documentable_id, $document->id]),
        ];
    }
}
