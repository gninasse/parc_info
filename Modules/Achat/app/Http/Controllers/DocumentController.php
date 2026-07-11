<?php

namespace Modules\Achat\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\Document;

class DocumentController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'document' => 'required|file|max:10240', // 10MB max
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'nom' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $docType = $request->input('documentable_type');
        $docId = $request->input('documentable_id');

        if ($docType === 'bon_commande') {
            $modelClass = BonCommande::class;
        } elseif ($docType === 'bordereau') {
            $modelClass = BordereauLivraison::class;
        } else {
            return response()->json(['success' => false, 'message' => 'Type de document invalide.'], 400);
        }

        $model = $modelClass::findOrFail($docId);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $originalName = $file->getClientOriginalName();
            $nom = $request->input('nom') ?: $originalName;
            $mimeType = $file->getClientMimeType();
            $size = $file->getSize();
            $path = $file->store('achat_documents', 'public');

            $document = $model->documents()->create([
                'nom' => $nom,
                'fichier_path' => $path,
                'taille' => $size,
                'type_mime' => $mimeType,
                'notes' => $request->input('notes'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Document ajouté avec succès.',
                'document' => [
                    'id' => $document->id,
                    'nom' => $document->nom,
                    'taille' => $document->taille,
                    'type_mime' => $document->type_mime,
                    'fichier_url' => asset('storage/'.$document->fichier_path),
                    'date' => $document->created_at->format('d/m/Y H:i'),
                ],
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Aucun fichier fourni.'], 400);
    }

    public function download(int $id)
    {
        $document = Document::findOrFail($id);

        if (! Storage::disk('public')->exists($document->fichier_path)) {
            abort(404, 'Fichier introuvable.');
        }

        return Storage::disk('public')->download($document->fichier_path, $document->nom);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $document = Document::findOrFail($id);
            $document->delete();

            return response()->json(['success' => true, 'message' => 'Document supprimé avec succès.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Erreur lors de la suppression.'], 500);
        }
    }
}
