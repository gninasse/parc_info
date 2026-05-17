<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\ParcInfo\Http\Requests\StoreLicenceRequest;
use Modules\ParcInfo\Http\Requests\UpdateLicenceRequest;
use Modules\ParcInfo\Models\AffectationLicence;
use Modules\ParcInfo\Models\Contact;
use Modules\ParcInfo\Models\ContratMaintenance;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;

class LicenceController extends Controller
{
    public function index()
    {
        $logiciels = Logiciel::orderBy('nom')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $contacts = Contact::where('est_actif', true)->orderBy('nom')->get();
        $contrats = ContratMaintenance::where('est_actif', true)->orderBy('reference')->get();

        return view('parcinfo::informatique.licences.index', compact('logiciels', 'fournisseurs', 'contacts', 'contrats'));
    }

    public function getData(Request $request)
    {
        $query = Licence::with(['logiciel', 'fournisseur']);

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('logiciel_id')) {
            $query->where('logiciel_id', $request->logiciel_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('cle_licence', 'ilike', "%{$s}%")
                    ->orWhere('numero_contrat', 'ilike', "%{$s}%")
                    ->orWhereHas('logiciel', fn ($q2) => $q2->where('nom', 'ilike', "%{$s}%"));
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
            'stats' => [
                'total' => Licence::where('actif', true)->count(),
                'expirant_30j' => Licence::expirantProchainement()->count(),
                'expiries' => Licence::expire()->count(),
                'surexploitees' => Licence::enSurexploitation()->count(),
                'cout_total_annuel' => Licence::where('actif', true)->sum('cout_total'),
            ],
        ]);
    }

    public function store(StoreLicenceRequest $request)
    {
        $licence = Licence::create($request->validated());

        activity('licence')
            ->performedOn($licence)
            ->causedBy(auth()->user())
            ->log('Création de licence');

        return response()->json([
            'success' => true,
            'message' => 'Licence créée avec succès',
            'licence_id' => $licence->id,
        ]);
    }

    public function show($id)
    {
        $licence = Licence::with([
            'logiciel.editeur',
            'fournisseur.contactPrincipal',
            'contactSupport',
            'affectations.employe',
            'affectations.equipement',
            'contratMaintenance',
            'documents',
        ])->findOrFail($id);

        if (request()->wantsJson() || request()->has('json')) {
            return response()->json($licence);
        }

        $logiciels = Logiciel::orderBy('nom')->get();
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get();
        $contacts = Contact::where('est_actif', true)->orderBy('nom')->get();
        $contrats = ContratMaintenance::where('est_actif', true)->orderBy('reference')->get();

        return view('parcinfo::informatique.licences.show', compact('licence', 'logiciels', 'fournisseurs', 'contacts', 'contrats'));
    }

    public function update(UpdateLicenceRequest $request, $id)
    {
        $licence = Licence::findOrFail($id);
        $licence->update($request->validated());

        activity('licence')
            ->performedOn($licence)
            ->causedBy(auth()->user())
            ->log('Mise à jour de licence');

        return response()->json(['success' => true, 'message' => 'Licence mise à jour avec succès']);
    }

    public function toggleStatus($id)
    {
        $licence = Licence::findOrFail($id);
        $licence->actif = ! $licence->actif;
        $licence->save();

        activity('licence')
            ->performedOn($licence)
            ->log($licence->actif ? 'Activation' : 'Désactivation');

        return response()->json(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function destroy($id)
    {
        $licence = Licence::findOrFail($id);

        if ($licence->affectations()->where('actif', true)->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer une licence ayant des affectations actives.'], 422);
        }

        $licence->delete();

        return response()->json(['success' => true, 'message' => 'Licence supprimée.']);
    }

    public function affecter(Request $request, $id)
    {
        $licence = Licence::findOrFail($id);

        $request->validate([
            'type_affectation' => 'required|in:device,user,concurrent',
            'equipement_id' => 'required_if:type_affectation,device|exists:parc_info_equipements,id',
            'employe_id' => 'required_if:type_affectation,user|exists:grh_dossiers_employes,dossier_employe_id',
        ]);

        if ($licence->nombre_postes_utilises >= $licence->nombre_postes_accordes && $licence->nombre_postes_accordes > 0) {
            return response()->json(['success' => false, 'message' => 'Aucun poste disponible pour cette licence'], 422);
        }

        DB::transaction(function () use ($licence, $request) {
            AffectationLicence::create([
                'licence_id' => $licence->id,
                'type_affectation' => $request->type_affectation,
                'equipement_id' => $request->equipement_id,
                'employe_id' => $request->employe_id,
                'date_affectation' => now(),
                'actif' => true,
            ]);

            $licence->increment('nombre_postes_utilises');
        });

        return response()->json(['success' => true, 'message' => 'Affectation réussie']);
    }

    public function renouveler(Request $request, $id)
    {
        $licence = Licence::findOrFail($id);

        $request->validate([
            'date_nouvelle_expiration' => 'required|date|after:'.$licence->date_expiration->format('Y-m-d'),
            'cout_renouvellement' => 'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use ($licence, $request) {
            $licence->update([
                'date_expiration' => $request->date_nouvelle_expiration,
                'statut' => 'actif',
                'cout_total' => $licence->cout_total + ($request->cout_renouvellement ?? 0),
            ]);

            activity('licence')
                ->performedOn($licence)
                ->log('Renouvellement de licence');
        });

        return response()->json(['success' => true, 'message' => 'Licence renouvelée avec succès']);
    }

    private function formatRow(Licence $l): array
    {
        return [
            'id' => $l->id,
            'logiciel' => $l->logiciel->nom,
            'contrat' => $l->numero_contrat ?: '—',
            'expiration' => $l->date_expiration->format('d/m/Y'),
            'statut_validite' => $l->statut_validite,
            'utilisation' => $l->taux_utilisation,
            'postes' => "{$l->nombre_postes_utilises} / {$l->nombre_postes_accordes}",
            'cout' => number_format($l->cout_total, 2, ',', ' ')." {$l->devise}",
            'statut' => $l->statut,
            'actif' => $l->actif,
            'status_label' => $this->getStatusLabel($l),
        ];
    }

    private function getStatusLabel(Licence $l): string
    {
        $class = match ($l->statut) {
            'actif' => 'success',
            'expire' => 'danger',
            'en_renouvellement' => 'primary',
            'suspendu' => 'warning',
            default => 'secondary',
        };

        return '<span class="badge bg-'.$class.'">'.ucfirst($l->statut).'</span>';
    }

    // QuickAdd methods
    public function storeFournisseur(Request $request)
    {
        $validated = $request->validate([
            'nom' => 'required|string|unique:parc_info_fournisseurs,nom',
            'type' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string',
        ]);

        $code = 'FOUR-' . strtoupper(substr($validated['nom'], 0, 3)) . rand(100, 999);
        $validated['code'] = $code;
        $validated['est_actif'] = true;

        $f = Fournisseur::create($validated);

        return response()->json(['success' => true, 'data' => $f, 'message' => 'Fournisseur ajouté avec succès.']);
    }

    public function storeContrat(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:parc_info_contrats_maintenances,reference',
            'nom' => 'required|string',
            'fournisseur_id' => 'required|exists:parc_info_fournisseurs,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'cout' => 'nullable|numeric|min:0',
        ]);

        $validated['est_actif'] = true;
        $contrat = ContratMaintenance::create($validated);

        return response()->json(['success' => true, 'data' => $contrat, 'message' => 'Contrat de maintenance enregistré avec succès.']);
    }
}
