<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\CameraIP;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeCamera;

class CameraController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $typesCameras = TypeCamera::orderBy('libelle')->get(['id', 'libelle']);
        return view('parcinfo::informatique.cameras.index', compact('sites', 'typesCameras'));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'camera', 'affectationActive.local.etage.batiment'])
            ->whereHas('camera');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }

        return response()->json([
            'total' => $query->count(),
            'rows' => $query->get()->map(fn ($e) => $this->formatRow($e)),
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderBy('id', 'desc')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge(['code_inventaire' => 'CAM-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'adresse_ip' => 'nullable|ip',
            'adresse_mac' => 'nullable|string|unique:parc_info_cameras_ip,adresse_mac',
            'resolution' => 'nullable|string',
            'type_camera_id' => 'nullable|exists:parc_info_types_cameras,id',
            'emplacement' => 'nullable|string',
        ]);

        $equipementId = \DB::transaction(function () use ($request) {
            $equipement = Equipement::create([
                'code_inventaire' => $request->code_inventaire,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'statut' => $request->statut,
                'etat' => $request->etat ?? 'bon',
            ]);

            CameraIP::create([
                'equipement_id' => $equipement->id,
                'adresse_ip' => $request->adresse_ip,
                'adresse_mac' => $request->adresse_mac,
                'resolution' => $request->resolution,
                'type_camera_id' => $request->type_camera_id,
                'emplacement' => $request->emplacement,
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible')) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => $request->type_cible,
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'local_id' => $request->local_id,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Caméra IP enregistrée avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'camera',
            'affectationActive.local.etage.batiment.site',
            'affectations.local',
            'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $typesCameras = TypeCamera::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.cameras.show', compact(
            'equipement', 'marques', 'sites', 'directions', 'typesCameras'
        ));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'adresse_ip' => 'nullable|ip',
            'adresse_mac' => "nullable|string|unique:parc_info_cameras_ip,adresse_mac,{$id},equipement_id",
            'resolution' => 'nullable|string',
            'type_camera_id' => 'nullable|exists:parc_info_types_cameras,id',
            'emplacement' => 'nullable|string',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'statut', 'etat',
            ]));

            $equipement->camera->update([
                'adresse_ip' => $request->adresse_ip,
                'adresse_mac' => $request->adresse_mac,
                'resolution' => $request->resolution,
                'type_camera_id' => $request->type_camera_id,
                'emplacement' => $request->emplacement,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Caméra IP mise à jour avec succès.']);
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

            if ($request->statut === 'en_stock' && $equipement->affectationActive) {
                AffectationEquipement::where('equipement_id', $id)
                    ->where('statut', true)
                    ->update(['statut' => false, 'date_fin' => now()]);
            }

            $equipement->update(['statut' => $request->statut]);

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
        return response()->json(['success' => true, 'message' => 'Caméra IP supprimée.']);
    }

    public function storeTypeCamera(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_cameras,libelle']);
        $type = TypeCamera::create(['libelle' => $request->libelle]);
        return response()->json(['success' => true, 'data' => $type]);
    }

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = $aff->local?->libelle ?? '—';
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'type_camera' => $e->camera->typeCamera?->libelle ?? '—',
            'resolution' => $e->camera->resolution ?? '—',
            'adresse_ip' => $e->camera->adresse_ip ?? '—',
            'emplacement' => $e->camera->emplacement ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
        ];
    }
}
