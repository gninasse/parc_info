<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Infrastructure;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeInfrastructure;

class BrassageController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.brassage.index', compact('sites', 'directions', 'marques'));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'infrastructure.typeInfrastructure', 'affectationActive.local'])
            ->whereHas('infrastructure.typeInfrastructure', fn ($q) => $q->where('libelle', 'ilike', '%brassage%'));

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }
        if ($request->filled('direction_id')) {
            $query->whereHas('affectationActive', fn ($q) => $q->where('direction_id', $request->direction_id));
        }
        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', 'ilike', "%{$s}%")
                ->orWhere('numero_serie', 'ilike', "%{$s}%")
                ->orWhere('modele', 'ilike', "%{$s}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', 'ilike', "%{$s}%"))
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
            $request->merge(['code_inventaire' => 'BRAS-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'nb_ports' => 'required|integer|min:1',
            'categorie_cable' => 'nullable|string|max:50',
            'type_connecteur' => 'nullable|string|max:50',
            'u_taille' => 'nullable|integer|min:1',
            'type_cible' => 'nullable|in:LOCAL',
            'skip_affectation' => 'nullable|boolean',
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

            $typeInfra = TypeInfrastructure::firstOrCreate(['libelle' => 'BRASSAGE']);

            Infrastructure::create([
                'equipement_id' => $equipement->id,
                'type_infra_id' => $typeInfra->id,
                'nb_ports' => $request->nb_ports,
                'categorie_cable' => $request->categorie_cable,
                'type_connecteur' => $request->type_connecteur,
                'u_taille' => $request->u_taille,
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible')) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => 'LOCAL',
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'local_id' => $request->local_id,
                    // Brassage : jamais affecté à un employé/poste
                    'direction_id' => null,
                    'service_id' => null,
                    'unite_id' => null,
                    'niveau_rattachement' => null,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Panneau de brassage enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'infrastructure.typeInfrastructure',
            'affectationActive.local.etage.batiment.site',
            'affectations.local',
            'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.brassage.show', compact('equipement', 'marques', 'sites', 'directions'));
    }

    public function showJson($id)
    {
        $e = Equipement::with([
            'marque',
            'infrastructure.typeInfrastructure',
            'affectationActive.local.etage.batiment.site',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $e]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'nb_ports' => 'required|integer|min:1',
            'categorie_cable' => 'nullable|string|max:50',
            'type_connecteur' => 'nullable|string|max:50',
            'u_taille' => 'nullable|integer|min:1',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'statut', 'etat',
            ]));

            $equipement->infrastructure->update([
                'nb_ports' => $request->nb_ports,
                'categorie_cable' => $request->categorie_cable,
                'type_connecteur' => $request->type_connecteur,
                'u_taille' => $request->u_taille,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Panneau de brassage mis à jour avec succès.']);
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
                'ancien_etat' => $ancienEtat,
                'nouvel_etat' => $request->etat,
                'motif' => $request->motif,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'État mis à jour avec succès.']);
    }

    public function desaffecter(Request $request, $id)
    {
        $request->validate([
            'motif' => 'required|string',
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

        return response()->json(['success' => true, 'message' => 'Panneau de brassage supprimé.']);
    }

    // ── AJAX helpers ──────────────────────────────────────────────────────────

    public function storeAffectation(Request $request)
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
                'date_fin' => null,
                'local_id' => $request->local_id,
                // Brassage : champs org toujours null
                'direction_id' => null,
                'service_id' => null,
                'unite_id' => null,
                'niveau_rattachement' => null,
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
                'motif' => 'Nouvelle affectation à un local',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    public function storeMarque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $marque]);
    }

    public function searchLocaux(Request $request)
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

    // ── Formatage ligne tableau ───────────────────────────────────────────────

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = $aff->local?->libelle ?? '—';
        }

        $infra = $e->infrastructure;

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'nb_ports' => $infra?->nb_ports ?? '—',
            'categorie_cable' => $infra?->categorie_cable ?? '—',
            'type_connecteur' => $infra?->type_connecteur ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
