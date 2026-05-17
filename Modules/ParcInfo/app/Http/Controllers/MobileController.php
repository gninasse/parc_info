<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\Mobile;
use Modules\ParcInfo\Models\TypeMobile;

class MobileController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesMobiles = TypeMobile::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.mobiles.index', compact(
            'sites', 'directions', 'marques', 'typesMobiles'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'mobile.typeMobile', 'affectationActive.employe'])
            ->whereHas('mobile');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_mobile_id')) {
            $query->whereHas('mobile', fn ($q) => $q->where('type_mobile_id', $request->type_mobile_id));
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.posteTravail.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }

        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', 'ilike', "%{$s}%")
                ->orWhere('numero_serie', 'ilike', "%{$s}%")
                ->orWhere('modele', 'ilike', "%{$s}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', 'ilike', "%{$s}%"))
                ->orWhereHas('mobile', fn ($q2) => $q2->where('imei_1', 'ilike', "%{$s}%"))
                ->orWhereHas('mobile', fn ($q2) => $q2->where('num_tel_associe', 'ilike', "%{$s}%"))
            );
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(fn ($e) => $this->formatRow($e)),
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderBy('id', 'desc')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge(['code_inventaire' => 'MOB-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            // Mobile
            'type_mobile_id' => 'nullable|exists:parc_info_types_mobiles,id',
            'imei_1' => 'nullable|string|unique:parc_info_mobiles,imei_1',
            'num_tel_associe' => 'nullable|string',
        ]);

        $equipementId = \DB::transaction(function () use ($request) {
            $equipement = Equipement::create([
                'code_inventaire' => $request->code_inventaire,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'statut' => $request->statut,
                'etat' => $request->etat ?? 'bon',
            ]);

            Mobile::create([
                'equipement_id' => $equipement->id,
                'type_mobile_id' => $request->type_mobile_id,
                'imei_1' => $request->imei_1,
                'imei_2' => $request->imei_2,
                'num_tel_associe' => $request->num_tel_associe,
                'version_os' => $request->version_os,
                'statut_mdm' => $request->statut_mdm,
                'capacite_batterie_mah' => $request->capacite_batterie_mah,
                'etat_ecran' => $request->etat_ecran,
                'a_coque_protection' => $request->boolean('a_coque_protection'),
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible')) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => $request->type_cible,
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'dossier_employe_id' => $request->dossier_employe_id,
                    'poste_travail_id' => $request->poste_travail_id,
                    'local_id' => $request->local_id,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Terminal mobile enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'mobile.typeMobile',
            'affectationActive.employe',
            'affectationActive.posteTravail',
            'affectationActive.local.etage.batiment.site',
            'affectations.employe',
            'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesMobiles = TypeMobile::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.mobiles.show', compact(
            'equipement', 'marques', 'typesMobiles', 'sites', 'directions'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'statut', 'etat',
            ]));

            $equipement->mobile->update($request->only([
                'type_mobile_id', 'imei_1', 'imei_2', 'num_tel_associe',
                'version_os', 'statut_mdm', 'capacite_batterie_mah', 'etat_ecran', 'a_coque_protection',
            ]));
        });

        return response()->json(['success' => true, 'message' => 'Mobile mis à jour avec succès.']);
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'motif' => 'required|string',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $ancienStatut = $equipement->statut;

            // Si passage en stock, désaffecter
            if ($request->statut === 'en_stock' && $equipement->affectationActive) {
                AffectationEquipement::where('equipement_id', $id)
                    ->where('statut', true)
                    ->update(['statut' => false, 'date_fin' => now()]);
            }

            $equipement->update(['statut' => $request->statut]);

            // Enregistrer dans l'historique
            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $request->statut,
                'motif' => $request->motif,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
    }

    public function updateEtat(Request $request, $id)
    {
        $request->validate([
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'motif' => 'required|string',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $ancienEtat = $equipement->etat;

            $equipement->update(['etat' => $request->etat]);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'ETAT',
                'ancien_statut' => $ancienEtat,
                'nouveau_statut' => $request->etat,
                'motif' => $request->motif,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'État mis à jour avec succès.']);
    }

    public function desaffecter(Request $request, $id)
    {
        $request->validate([
            'motif' => 'required|string|max:255',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $ancienStatut = $equipement->statut;

            AffectationEquipement::where('equipement_id', $id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $equipement->update(['statut' => 'en_stock']);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'AFFECTATION',
                'ancien_statut' => null,
                'nouveau_statut' => null,
                'motif' => 'Désaffectation : '.$request->motif,
            ]);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => 'en_stock',
                'motif' => 'Mise en stock automatique suite à désaffectation',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Équipement désaffecté et mis en stock.']);
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Terminal supprimé.']);
    }

    public function storeTypeMobile(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_mobiles,libelle']);
        $type = TypeMobile::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $type]);
    }

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = match ($aff->type_cible) {
                'EMPLOYE' => $aff->employe?->full_name ?? '—',
                'POSTE' => $aff->posteTravail?->code ?? '—',
                'LOCAL' => $aff->local?->libelle ?? '—',
                default => '—',
            };
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'type_mobile' => $e->mobile->typeMobile?->libelle ?? '—',
            'num_tel' => $e->mobile->num_tel_associe ?? '—',
            'imei' => $e->mobile->imei_1 ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
        ];
    }
}
