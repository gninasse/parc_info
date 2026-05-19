<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Infrastructure;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeInfrastructure;

class RackController extends Controller
{
    private function rackScope()
    {
        return Equipement::query()->whereHas('infrastructure.typeInfrastructure', function ($q) {
            $q->where('libelle', 'ilike', '%rack%')
                ->orWhere('libelle', 'ilike', '%baie%');
        });
    }

    private function findRackOrFail(int $id, array $with = []): Equipement
    {
        return $this->rackScope()
            ->with($with)
            ->findOrFail($id);
    }

    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesInfra = TypeInfrastructure::query()
            ->where(fn ($q) => $q->where('libelle', 'ilike', '%rack%')->orWhere('libelle', 'ilike', '%baie%'))
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        return view('parcinfo::informatique.racks.index', compact(
            'sites', 'directions', 'marques', 'typesInfra'
        ));
    }

    public function getData(Request $request)
    {
        $query = $this->rackScope()
            ->with(['marque', 'infrastructure.typeInfrastructure', 'affectationActive.local.etage.batiment.site']);

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
            $search = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', 'ilike', "%{$search}%")
                ->orWhere('numero_serie', 'ilike', "%{$search}%")
                ->orWhere('modele', 'ilike', "%{$search}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', 'ilike', "%{$search}%"))
            );
        }

        $sortableFields = ['id', 'code_inventaire', 'numero_serie', 'modele', 'statut', 'etat'];
        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc') === 'asc' ? 'asc' : 'desc';
        $query->orderBy(in_array($sortField, $sortableFields, true) ? $sortField : 'id', $sortOrder);

        $total = (clone $query)->count();
        $rows = $query
            ->offset((int) $request->get('offset', 0))
            ->limit((int) $request->get('limit', 25))
            ->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(fn (Equipement $e) => $this->formatRow($e))->values(),
        ]);
    }

    public function store(Request $request)
    {
        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderByDesc('id')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge([
                'code_inventaire' => 'RACK-'.date('Y').'-'.str_pad((string) $nextId, 4, '0', STR_PAD_LEFT),
            ]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'date_fin_garantie' => 'nullable|date',
            'valeur_achat' => 'nullable|numeric|min:0',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'u_capacite_totale' => 'nullable|integer|min:1',
            'nb_prises_pdu' => 'nullable|integer|min:0',
            'est_redondant' => 'nullable|boolean',
            'type_cible' => 'nullable|in:LOCAL',
            'local_id' => 'nullable|exists:organisation_locaux,id',
            'skip_affectation' => 'nullable|boolean',
        ]);

        $equipementId = DB::transaction(function () use ($request) {
            $equipement = Equipement::create([
                'code_inventaire' => $request->code_inventaire,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie' => $request->date_fin_garantie,
                'valeur_achat' => $request->valeur_achat,
                'statut' => $request->statut,
                'etat' => $request->etat,
                'tags' => $request->filled('tags') ? explode(',', (string) $request->tags) : null,
            ]);

            $typeInfra = TypeInfrastructure::firstOrCreate(['libelle' => 'RACK']);

            Infrastructure::create([
                'equipement_id' => $equipement->id,
                'type_infra_id' => $typeInfra->id,
                'u_capacite_totale' => $request->u_capacite_totale,
                'nb_prises_pdu' => $request->nb_prises_pdu,
                'est_redondant' => $request->boolean('est_redondant'),
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible') && $request->filled('local_id')) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => 'LOCAL',
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now(),
                    'local_id' => $request->local_id,
                    'niveau_rattachement' => null,
                    'direction_id' => null,
                    'service_id' => null,
                    'unite_id' => null,
                ]);
            }

            return $equipement->id;
        });

        return response()->json([
            'success' => true,
            'message' => 'Baie/rack enregistré avec succès.',
            'equipement_id' => $equipementId,
        ]);
    }

    public function show(int $id)
    {
        $equipement = $this->findRackOrFail($id, [
            'marque',
            'infrastructure.typeInfrastructure',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction',
            'affectationActive.service',
            'affectations.local',
            'affectations.direction',
            'historique',
        ]);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $typesInfra = TypeInfrastructure::query()
            ->where(fn ($q) => $q->where('libelle', 'ilike', '%rack%')->orWhere('libelle', 'ilike', '%baie%'))
            ->orderBy('libelle')
            ->get(['id', 'libelle']);

        return view('parcinfo::informatique.racks.show', compact(
            'equipement', 'marques', 'sites', 'directions', 'typesInfra'
        ));
    }

    public function showJson(int $id)
    {
        $equipement = $this->findRackOrFail($id, [
            'marque',
            'infrastructure.typeInfrastructure',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction',
            'affectationActive.service',
        ]);

        return response()->json(['success' => true, 'data' => $equipement]);
    }

    public function update(Request $request, int $id)
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'date_fin_garantie' => 'nullable|date',
            'valeur_achat' => 'nullable|numeric|min:0',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'u_capacite_totale' => 'nullable|integer|min:1',
            'nb_prises_pdu' => 'nullable|integer|min:0',
            'est_redondant' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = $this->findRackOrFail($id, ['infrastructure']);

            $equipement->update([
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie' => $request->date_fin_garantie,
                'valeur_achat' => $request->valeur_achat,
                'statut' => $request->statut,
                'etat' => $request->etat,
            ]);

            $equipement->infrastructure->update([
                'u_capacite_totale' => $request->u_capacite_totale,
                'nb_prises_pdu' => $request->nb_prises_pdu,
                'est_redondant' => $request->boolean('est_redondant'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Baie/rack mis à jour avec succès.']);
    }

    public function updateStatut(Request $request, int $id)
    {
        $request->validate([
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = $this->findRackOrFail($id);
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
                'utilisateur_id' => Auth::id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $request->statut,
                'motif' => $request->motif,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Statut mis à jour avec succès.']);
    }

    public function updateEtat(Request $request, int $id)
    {
        $request->validate([
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = $this->findRackOrFail($id);
            $ancienEtat = $equipement->etat;

            $equipement->update(['etat' => $request->etat]);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => Auth::id(),
                'type_changement' => 'ETAT',
                'ancien_etat' => $ancienEtat,
                'nouvel_etat' => $request->etat,
                'motif' => $request->motif,
            ]);
        });

        return response()->json(['success' => true, 'message' => 'État mis à jour avec succès.']);
    }

    public function desaffecter(Request $request, int $id)
    {
        $request->validate([
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = $this->findRackOrFail($id);
            $ancienStatut = $equipement->statut;

            AffectationEquipement::where('equipement_id', $id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $equipement->update(['statut' => 'en_stock']);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => Auth::id(),
                'type_changement' => 'AFFECTATION',
                'ancien_statut' => null,
                'nouveau_statut' => null,
                'motif' => 'Désaffectation : '.$request->motif,
            ]);

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => Auth::id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => 'en_stock',
                'motif' => 'Mise en stock automatique suite à désaffectation',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Baie/rack désaffecté et remis en stock.']);
    }

    public function destroy(int $id)
    {
        $this->findRackOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Baie/rack supprimé.']);
    }

    public function storeAffectation(Request $request)
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:LOCAL',
            'local_id' => 'required|exists:organisation_locaux,id',
        ]);

        DB::transaction(function () use ($request) {
            $equipement = $this->findRackOrFail((int) $request->equipement_id);
            $ancienStatut = $equipement->statut;

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
                'niveau_rattachement' => null,
                'direction_id' => null,
                'service_id' => null,
                'unite_id' => null,
            ]);

            if ($equipement->statut === 'en_stock') {
                $equipement->update(['statut' => 'en_service']);

                HistoriqueChangement::create([
                    'equipement_id' => $request->equipement_id,
                    'date_changement' => now(),
                    'utilisateur_id' => Auth::id(),
                    'type_changement' => 'STATUT',
                    'ancien_statut' => $ancienStatut,
                    'nouveau_statut' => 'en_service',
                    'motif' => 'Mise en service automatique suite à affectation',
                ]);
            }

            HistoriqueChangement::create([
                'equipement_id' => $request->equipement_id,
                'date_changement' => now(),
                'utilisateur_id' => Auth::id(),
                'type_changement' => 'AFFECTATION',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $equipement->fresh()->statut,
                'motif' => 'Nouvelle affectation locale',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    public function searchLocaux(Request $request)
    {
        $q = $request->get('q', '');

        return response()->json(
            Local::with(['etage.batiment.site'])
                ->where(fn ($query) => $query
                    ->where('libelle', 'ilike', "%{$q}%")
                    ->orWhere('code', 'ilike', "%{$q}%"))
                ->limit(20)
                ->get()
                ->map(fn (Local $local) => [
                    'id' => $local->id,
                    'text' => $local->nom_complet,
                    'code' => $local->code,
                    'libelle' => $local->libelle,
                    'type' => $local->type_local_label,
                    'etage' => $local->etage?->libelle,
                    'batiment' => $local->etage?->batiment?->libelle,
                ])
        );
    }

    public function storeMarque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $marque]);
    }

    private function formatRow(Equipement $e): array
    {
        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => trim(($e->marque?->libelle ?? '—').' '.$e->modele),
            'u_capacite_totale' => $e->infrastructure?->u_capacite_totale ?? '—',
            'nb_prises_pdu' => $e->infrastructure?->nb_prises_pdu ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $e->affectationActive?->local?->nom_complet ?? ($e->statut === 'en_stock' ? 'En stock' : '—'),
            'etat' => $e->etat,
        ];
    }
}
