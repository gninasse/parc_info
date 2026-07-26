<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Http\Controllers\Concerns\RepondEnJson;
use Modules\Achat\Http\Requests\StoreDocumentRequest;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\Document;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Pièces jointes des bons de commande et des bordereaux.
 *
 * EF-DOC-06 — Correction AN-17. La version précédente n'effectuait aucun
 * contrôle d'habilitation : tout utilisateur authentifié pouvait téléverser,
 * télécharger ou supprimer la pièce jointe de n'importe quel document.
 */
class DocumentController extends Controller
{
    use AuthorizesRequests, RepondEnJson;

    /** Types rattachables, exposés sous un alias stable côté client. */
    protected const TYPES = [
        'bon_commande' => BonCommande::class,
        'bordereau' => BordereauLivraison::class,
    ];

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        return $this->executer(function () use ($request) {
            $classe = self::TYPES[$request->input('documentable_type')];
            $porteur = $classe::findOrFail($request->integer('documentable_id'));

            // L'accès à la pièce suit l'accès au document porteur.
            $this->authorize($this->permissionDeConsultation($porteur));

            $fichier = $request->file('document');

            $document = $porteur->documents()->create([
                'nom' => $request->input('nom') ?: $fichier->getClientOriginalName(),
                'fichier_path' => $fichier->store(
                    config('achat.documents.repertoire', 'achat_documents'),
                    config('achat.documents.disque', 'public')
                ),
                'taille' => $fichier->getSize(),
                'type_mime' => $fichier->getClientMimeType(),
                'notes' => $request->input('notes'),
            ]);

            activity()->performedOn($porteur)->log("Document « {$document->nom} » joint");

            return $this->succes('Document ajouté.', [
                'data' => [
                    'id' => $document->id,
                    'nom' => $document->nom,
                    'notes' => $document->notes,
                    'taille_lisible' => $document->taille_lisible,
                    'icone' => $document->icone,
                    'auteur' => auth()->user()?->name,
                    'date' => $document->created_at->format('d/m/Y H:i'),
                    'url_telechargement' => route('achat.documents.telecharger', $document),
                ],
            ]);
        });
    }

    public function download(Document $document): StreamedResponse
    {
        $this->authorize('achat.documents.view');
        $this->authorize($this->permissionDeConsultation($document->documentable));

        $disque = config('achat.documents.disque', 'public');

        abort_unless(
            Storage::disk($disque)->exists($document->fichier_path),
            404,
            'Le fichier est introuvable sur le serveur.'
        );

        return Storage::disk($disque)->download($document->fichier_path, $document->nom);
    }

    public function destroy(Document $document): JsonResponse
    {
        $this->authorize('achat.documents.delete');

        return $this->executer(function () use ($document) {
            $this->authorize($this->permissionDeConsultation($document->documentable));

            $nom = $document->nom;
            $porteur = $document->documentable;

            // Suppression logique : le fichier physique est conservé pour la
            // piste d'audit (ENF-TRA-02).
            $document->delete();

            if ($porteur) {
                activity()->performedOn($porteur)->log("Document « {$nom} » supprimé");
            }

            return $this->succes('Document supprimé.');
        });
    }

    /** Permission de consultation du document porteur. */
    protected function permissionDeConsultation(mixed $porteur): string
    {
        return match (true) {
            $porteur instanceof BonCommande => 'achat.bons_commande.view',
            $porteur instanceof BordereauLivraison => 'achat.bordereaux.view',
            default => 'achat.documents.view',
        };
    }
}
