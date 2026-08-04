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
            'data' => $bon->documents()->with('createur:id,name')->get()->map(fn (Document $doc) => $this->presenter($doc, $type)),
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
        ], [
            'fichiers.*.mimes' => 'Format non accepté : joignez un PDF, une image ou un document bureautique.',
            'fichiers.*.max' => "Fichier trop volumineux (maximum {$tailleMax} Ko).",
        ]);

        $crees = DB::transaction(function () use ($bon, $valide, $type) {
            return collect($valide['fichiers'])->map(function ($fichier) use ($bon, $type) {
                $chemin = $fichier->store(Document::DOSSIER."/{$type}/{$bon->id}", Document::DISQUE);

                return $bon->documents()->create([
                    'nom_original' => $fichier->getClientOriginalName(),
                    'chemin' => $chemin,
                    'mime' => $fichier->getClientMimeType(),
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

        abort_unless(Storage::disk(Document::DISQUE)->exists($piece->chemin), 404, 'Fichier introuvable sur le disque.');

        return Storage::disk(Document::DISQUE)->download($piece->chemin, $piece->nom_original, [
            'Content-Type' => $piece->mime,
        ]);
    }

    public function destroy(string $type, int $id, int $document): JsonResponse
    {
        $bon = $this->bon($type, $id);
        $this->autoriserType($type, 'store');

        if ($bon->estValide()) {
            return response()->json([
                'success' => false,
                'message' => 'Bon validé — ses pièces jointes ne peuvent plus être modifiées.',
            ], 409);
        }

        $bon->documents()->findOrFail($document)->delete(); // le fichier suit

        return response()->json(['success' => true, 'message' => 'Pièce jointe supprimée.']);
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
            'nom' => $document->nom_original,
            'mime' => $document->mime,
            'taille' => $document->taille_lisible,
            'icone' => $document->icone,
            'est_image' => $document->estImage(),
            'est_pdf' => $document->estPdf(),
            'ajoute_le' => $document->created_at?->format('d/m/Y H:i'),
            'ajoute_par' => $document->createur?->name,
            'url' => route('stock.documents.download', [$type, $document->documentable_id, $document->id]),
        ];
    }
}
