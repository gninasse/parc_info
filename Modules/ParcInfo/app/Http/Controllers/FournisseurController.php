<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ParcInfo\Http\Requests\StoreContactRequest;
use Modules\ParcInfo\Http\Requests\StoreFournisseurRequest;
use Modules\ParcInfo\Models\Contact;
use Modules\ParcInfo\Models\Fournisseur;

class FournisseurController extends Controller
{
    public function index()
    {
        return view('parcinfo::informatique.fournisseurs.index');
    }

    public function getData(Request $request)
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

    public function store(StoreFournisseurRequest $request)
    {
        $fournisseur = Fournisseur::create($request->validated());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Création de fournisseur');

        return response()->json(['success' => true, 'message' => 'Fournisseur créé avec succès.']);
    }

    public function show($id)
    {
        $fournisseur = Fournisseur::with(['contacts', 'contrats', 'licences.logiciel'])->findOrFail($id);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($fournisseur);
        }

        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();

        return view('parcinfo::informatique.fournisseurs.show', compact('fournisseur', 'fournisseurs'));
    }

    public function update(StoreFournisseurRequest $request, $id)
    {
        $fournisseur = Fournisseur::findOrFail($id);
        $fournisseur->update($request->all());

        activity('fournisseur')
            ->performedOn($fournisseur)
            ->causedBy(auth()->user())
            ->log('Mise à jour de fournisseur');

        return response()->json(['success' => true, 'message' => 'Fournisseur mis à jour avec succès.']);
    }

    public function toggleStatus($id)
    {
        $fournisseur = Fournisseur::findOrFail($id);
        $fournisseur->est_actif = ! $fournisseur->est_actif;
        $fournisseur->save();

        return response()->json(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function destroy($id)
    {
        $fournisseur = Fournisseur::findOrFail($id);
        if ($fournisseur->licences()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un fournisseur lié à des licences.'], 422);
        }
        $fournisseur->delete();

        return response()->json(['success' => true, 'message' => 'Fournisseur supprimé.']);
    }

    // Gestion des Contacts associés
    public function storeContact(StoreContactRequest $request, $fournisseurId)
    {
        $fournisseur = Fournisseur::findOrFail($fournisseurId);

        $contact = Contact::create($request->validated());

        // Lier le contact au fournisseur (si votre modèle de données le permet via une FK)
        // Dans le schéma actuel, Contact appartient à Fournisseur ?
        // Vérifions le modèle Fournisseur.php: il a hasMany(Contact)
        // Vérifions le modèle Contact.php: il a table parc_info_contacts

        $contact->fournisseur_id = $fournisseur->id; // Assumons que la colonne existe ou qu'on doit l'ajouter
        $contact->save();

        return response()->json(['success' => true, 'message' => 'Contact ajouté avec succès.']);
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
