<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\Editeur;
use Modules\ParcInfo\Models\TypeLicence;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Contact;
use Modules\ParcInfo\Models\ContratMaintenance;
use Modules\ParcInfo\Http\Requests\StoreLogicielRequest;
use Illuminate\Support\Facades\DB;

class LogicielController extends Controller
{
    public function index()
    {
        $editeurs = Editeur::orderBy('nom')->get();
        $typesLicences = TypeLicence::orderBy('libelle')->get();

        return view('parcinfo::informatique.logiciels.index', compact('editeurs', 'typesLicences'));
    }

    public function getData(Request $request)
    {
        $query = Logiciel::with(['editeur', 'typeLicence']);

        if ($request->filled('editeur_id')) {
            $query->where('editeur_id', $request->editeur_id);
        }

        if ($request->filled('type_licence_id')) {
            $query->where('type_licence_id', $request->type_licence_id);
        }

        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('nom', 'ilike', "%{$s}%")
                    ->orWhere('code', 'ilike', "%{$s}%")
                    ->orWhereHas('editeur', fn ($q2) => $q2->where('nom', 'ilike', "%{$s}%"));
            });
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(fn ($l) => $this->formatRow($l)),
        ]);
    }

    public function store(StoreLogicielRequest $request)
    {
        $logiciel = Logiciel::create($request->validated());

        activity('logiciel')
            ->performedOn($logiciel)
            ->causedBy(auth()->user())
            ->log('Création de logiciel');

        return response()->json([
            'success' => true,
            'message' => 'Logiciel créé avec succès.',
            'logiciel_id' => $logiciel->id,
        ]);
    }

    public function show($id)
    {
        $logiciel = Logiciel::with(['editeur', 'typeLicence', 'licences'])->findOrFail($id);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($logiciel);
        }

        $logiciels = Logiciel::orderBy('nom')->get();
        $editeurs = Editeur::orderBy('nom')->get();
        $typesLicences = TypeLicence::orderBy('libelle')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $contacts = Contact::where('est_actif', true)->orderBy('nom')->get();
        $contrats = ContratMaintenance::where('est_actif', true)->orderBy('reference')->get();

        return view('parcinfo::informatique.logiciels.show', compact(
            'logiciel',
            'logiciels',
            'editeurs',
            'typesLicences',
            'fournisseurs',
            'contacts',
            'contrats'
        ));
    }

    public function update(Request $request, $id)
    {
        $logiciel = Logiciel::findOrFail($id);

        $request->validate([
            'code' => "required|string|max:255|unique:parc_info_logiciels,code,{$id}",
            'nom' => 'required|string|max:255',
            'editeur_id' => 'required|exists:parc_info_editeurs,id',
            'type_licence_id' => 'required|exists:parc_info_types_licences,id',
        ]);

        $logiciel->update($request->all());

        activity('logiciel')
            ->performedOn($logiciel)
            ->causedBy(auth()->user())
            ->log('Mise à jour de logiciel');

        return response()->json(['success' => true, 'message' => 'Logiciel mis à jour avec succès.']);
    }

    public function toggleStatus($id)
    {
        $logiciel = Logiciel::findOrFail($id);
        $logiciel->est_actif = ! $logiciel->est_actif;
        $logiciel->save();

        activity('logiciel')
            ->performedOn($logiciel)
            ->log($logiciel->est_actif ? 'Activation' : 'Désactivation');

        return response()->json(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function destroy($id)
    {
        $logiciel = Logiciel::findOrFail($id);

        if ($logiciel->licences()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un logiciel ayant des licences rattachées.'], 422);
        }

        $logiciel->delete();

        return response()->json(['success' => true, 'message' => 'Logiciel supprimé.']);
    }

    private function formatRow(Logiciel $l): array
    {
        return [
            'id' => $l->id,
            'code' => $l->code,
            'nom' => $l->nom,
            'editeur' => $l->editeur->nom,
            'type_licence' => $l->typeLicence->libelle,
            'categorie' => $l->categorie ?: '—',
            'nb_licences' => $l->licences()->count(),
            'est_actif' => $l->est_actif,
            'status_label' => $l->est_actif
                ? '<span class="badge bg-success">Actif</span>'
                : '<span class="badge bg-danger">Inactif</span>',
        ];
    }

    // QuickAdd methods
    public function storeEditeur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:parc_info_editeurs,nom',
            'site_web' => 'nullable|url|max:255',
            'email_support' => 'nullable|email|max:255',
            'telephone_support' => 'nullable|string|max:50',
        ]);

        $code = strtoupper(substr($validated['nom'], 0, 3)).rand(100, 999);
        $validated['code'] = $code;
        $validated['est_actif'] = true;

        $editeur = Editeur::create($validated);

        return response()->json(['success' => true, 'data' => $editeur, 'message' => 'Éditeur ajouté avec succès.']);
    }
}
