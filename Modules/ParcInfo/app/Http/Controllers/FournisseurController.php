<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\ParcInfo\Http\Requests\StoreContactRequest;
use Modules\ParcInfo\Http\Requests\StoreFournisseurRequest;
use Modules\ParcInfo\Http\Requests\UpdateContactRequest;
use Modules\ParcInfo\Models\Fournisseur;

class FournisseurController extends Controller
{
    public function index(): View
    {
        return view('parcinfo::informatique.fournisseurs.index');
    }

    public function getData(Request $request): JsonResponse
    {
        $query = Fournisseur::query();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'ilike', "%{$s}%")
                    ->orWhere('code', 'ilike', "%{$s}%")
                    ->orWhere('email', 'ilike', "%{$s}%");
            });
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(fn ($f) => $this->formatRow($f)),
        ]);
    }

    public function create(): View
    {
        return view('parcinfo::informatique.fournisseurs.create');
    }

    public function store(StoreFournisseurRequest $request): JsonResponse
    {
        $fournisseur = Fournisseur::create($request->validated());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Création de fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur créé avec succès.',
            'redirect' => route('parc-info.fournisseurs.show', $fournisseur->id),
        ]);
    }

    public function show(int $id): View|JsonResponse
    {
        $fournisseur = Fournisseur::with(['contacts', 'contrats', 'licences.logiciel'])->findOrFail($id);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($fournisseur);
        }

        return view('parcinfo::informatique.fournisseurs.show', compact('fournisseur'));
    }

    public function update(StoreFournisseurRequest $request, int $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);
        $fournisseur->update($request->validated());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Mise à jour de fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Fournisseur mis à jour avec succès.',
            'data' => $fournisseur,
        ]);
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);
        $fournisseur->est_actif = ! $fournisseur->est_actif;
        $fournisseur->save();

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Changement de statut du fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Statut mis à jour.',
            'est_actif' => $fournisseur->est_actif,
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);
        if ($fournisseur->licences()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un fournisseur lié à des licences.'], 422);
        }
        $fournisseur->delete();

        return response()->json(['success' => true, 'message' => 'Fournisseur supprimé.']);
    }

    // Gestion des Contacts associés
    public function storeContact(StoreContactRequest $request, int $fournisseurId): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($fournisseurId);

        $contact = $fournisseur->contacts()->create($request->validated());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Ajout d\'un contact pour le fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Contact ajouté avec succès.',
            'data' => $contact,
        ]);
    }

    public function updateContact(UpdateContactRequest $request, int $fournisseurId, int $contactId): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($fournisseurId);
        $contact = $fournisseur->contacts()->findOrFail($contactId);
        $contact->update($request->validated());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Mise à jour d\'un contact du fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Contact mis à jour avec succès.',
            'data' => $contact,
        ]);
    }

    public function deleteContact(int $fournisseurId, int $contactId): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($fournisseurId);
        $contact = $fournisseur->contacts()->findOrFail($contactId);
        $contact->delete();

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Suppression d\'un contact du fournisseur');

        return response()->json([
            'success' => true,
            'message' => 'Contact supprimé avec succès.',
        ]);
    }

    private function formatRow(Fournisseur $f): array
    {
        return [
            'id' => $f->id,
            'code' => $f->code,
            'nom' => $f->nom,
            'type' => $f->type ?: '—',
            'email' => $f->email ?: '—',
            'telephone' => $f->telephone ?: '—',
            'status_label' => $f->est_actif
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>',
        ];
    }
}
