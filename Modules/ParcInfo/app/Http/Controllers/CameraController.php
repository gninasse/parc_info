<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\CameraIP;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;

class CameraController extends Controller
{
    /**
     * Display the index view for Caméras IP.
     */
    public function index(): View
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        $pageTitle = 'Caméras IP';
        $dataUrl = route('parc-info.cameras.data');
        $routePrefix = 'parc-info.cameras';

        return view('parcinfo::informatique.cameras.index', compact(
            'sites', 'directions', 'marques', 'pageTitle', 'dataUrl', 'routePrefix'
        ));
    }

    /**
     * Get JSON data for Bootstrap Table.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = Equipement::query()
            ->with(['marque', 'camera', 'affectationActive.local.etage.batiment.site'])
            ->whereHas('camera');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }

        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', 'ilike', "%{$s}%")
                ->orWhere('numero_serie', 'ilike', "%{$s}%")
                ->orWhere('modele', 'ilike', "%{$s}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', 'ilike', "%{$s}%"))
                ->orWhereHas('camera', fn ($q2) => $q2->where('adresse_ip', 'ilike', "%{$s}%"))
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

    /**
     * Store a newly created Camera.
     */
    public function store(Request $request): JsonResponse
    {
        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderBy('id', 'desc')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge(['code_inventaire' => 'CAM-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            // Camera specific
            'adresse_ip' => 'nullable|ip',
            'adresse_mac' => 'nullable|string|max:255',
            'resolution' => 'nullable|string|max:255',
            'type_camera' => 'nullable|string|max:255',
            'emplacement' => 'nullable|string|max:255',
            'type_cible' => 'nullable|in:LOCAL',
            'skip_affectation' => 'nullable|boolean',
            'local_id' => 'nullable|exists:organisation_locaux,id',
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
                'type_camera' => $request->type_camera,
                'emplacement' => $request->emplacement,
            ]);

            if (! $request->boolean('skip_affectation') && $request->type_cible === 'LOCAL' && $request->local_id) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => 'LOCAL',
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'local_id' => $request->local_id,
                ]);
            }

            return $equipement->id;
        });

        return response()->json([
            'success' => true,
            'message' => 'Caméra IP enregistrée avec succès.',
            'equipement_id' => $equipementId,
        ]);
    }

    /**
     * Show a detailed technical view for Camera.
     */
    public function show($id): View
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
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        $routePrefix = 'parc-info.cameras';

        return view('parcinfo::informatique.cameras.show', compact(
            'equipement', 'marques', 'sites', 'directions', 'routePrefix'
        ));
    }

    /**
     * Update an existing Camera.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'adresse_ip' => 'nullable|ip',
            'adresse_mac' => 'nullable|string|max:255',
            'resolution' => 'nullable|string|max:255',
            'type_camera' => 'nullable|string|max:255',
            'emplacement' => 'nullable|string|max:255',
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
                'type_camera' => $request->type_camera,
                'emplacement' => $request->emplacement,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Caméra IP mise à jour avec succès.']);
    }

    /**
     * Update only the equipment status.
     */
    public function updateStatut(Request $request, $id): JsonResponse
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

    /**
     * Update only the equipment condition.
     */
    public function updateEtat(Request $request, $id): JsonResponse
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

    /**
     * Deallocate active allocation and set status to en_stock.
     */
    public function desaffecter(Request $request, $id): JsonResponse
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

        return response()->json(['success' => true, 'message' => 'Caméra IP désaffectée et mise en stock.']);
    }

    /**
     * Delete an existing Camera.
     */
    public function destroy($id): JsonResponse
    {
        Equipement::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Caméra IP supprimée.']);
    }

    /**
     * Create an allocation manually.
     */
    public function storeAffectation(Request $request): JsonResponse
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:LOCAL',
            'local_id' => 'required|exists:organisation_locaux,id',
        ]);

        \DB::transaction(function () use ($request) {
            $equipement = Equipement::findOrFail($request->equipement_id);

            AffectationEquipement::where('equipement_id', $request->equipement_id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            AffectationEquipement::create([
                'code' => 'AFF-'.strtoupper(uniqid()),
                'equipement_id' => $request->equipement_id,
                'statut' => true,
                'type_cible' => 'LOCAL',
                'type_affectation' => 'PERMANENTE',
                'date_debut' => now(),
                'local_id' => $request->local_id,
            ]);

            $ancienStatut = $equipement->statut;
            if ($equipement->statut === 'en_stock') {
                $equipement->update(['statut' => 'en_service']);

                HistoriqueChangement::create([
                    'equipement_id' => $request->equipement_id,
                    'date_changement' => now(),
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'STATUT',
                    'ancien_statut' => $ancienStatut,
                    'nouveau_statut' => 'en_service',
                    'motif' => 'Mise en service automatique suite à affectation',
                ]);
            }

            HistoriqueChangement::create([
                'equipement_id' => $request->equipement_id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'AFFECTATION',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $equipement->statut,
                'motif' => 'Nouvelle affectation',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    /**
     * AJAX search for local technical rooms.
     */
    public function searchLocaux(Request $request): JsonResponse
    {
        $q = $request->get('q', '');

        return response()->json(
            Local::with(['etage.batiment.site'])
                ->where(fn ($query) => $query
                    ->where('libelle', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%"))
                ->limit(20)->get()
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'text' => $l->nom_complet,
                ])
        );
    }

    /**
     * Create brand dynamically.
     */
    public function storeMarque(Request $request): JsonResponse
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $marque]);
    }

    /**
     * Format row database entries into detailed table payloads.
     */
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
            'adresse_ip' => $e->camera->adresse_ip ?? '—',
            'type_camera' => $e->camera->type_camera ?? '—',
            'resolution' => $e->camera->resolution ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
