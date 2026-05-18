<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Imprimante;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeImprimante;

class ImprimanteController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesImprimantes = TypeImprimante::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.imprimantes.index', compact(
            'sites', 'directions', 'marques', 'typesImprimantes'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'imprimante.typeImprimante', 'affectationActive.employe', 'affectationActive.posteTravail', 'affectationActive.local'])
            ->whereHas('imprimante');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.posteTravail.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id))
                ->orWhereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }
        if ($request->filled('direction_id')) {
            $query->whereHas('affectationActive', fn ($q) => $q->where('direction_id', $request->direction_id));
        }
        if ($request->filled('type_imprimante_id')) {
            $query->whereHas('imprimante', fn ($q) => $q->where('type_imprimante_id', $request->type_imprimante_id));
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
            $request->merge(['code_inventaire' => 'IMP-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'type_imprimante_id' => 'nullable|exists:parc_info_types_imprimantes,id',
            'adresse_ip' => 'nullable|ip',
            'type_cible' => 'nullable|in:EMPLOYE,POSTE,LOCAL',
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
                'tags' => $request->tags ? explode(',', $request->tags) : null,
            ]);

            Imprimante::create([
                'equipement_id' => $equipement->id,
                'type_imprimante_id' => $request->type_imprimante_id,
                'est_couleur' => $request->boolean('est_couleur'),
                'est_multifonction' => $request->boolean('est_multifonction'),
                'fonctions' => $request->fonctions,
                'adresse_ip' => $request->adresse_ip,
                'snmp_community' => $request->snmp_community,
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
                    'direction_id' => $request->direction_id_aff,
                    'service_id' => $request->service_id_aff,
                ]);
            }

            // Historique initial
            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'nouveau_statut' => $request->statut,
                'nouvel_etat' => $request->etat,
                'motif' => 'Enregistrement initial de l\'équipement',
            ]);

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Imprimante enregistrée avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque', 'imprimante.typeImprimante',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service',
            'affectations.employe', 'affectations.posteTravail', 'affectations.local',
            'affectations.direction', 'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesImprimantes = TypeImprimante::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.imprimantes.show', compact(
            'equipement', 'marques', 'typesImprimantes', 'sites', 'directions'
        ));
    }

    public function showJson($id)
    {
        $e = Equipement::with([
            'marque', 'imprimante.typeImprimante',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $e]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'date_mise_en_service', 'date_fin_garantie',
                'valeur_achat', 'statut', 'etat',
            ]));

            $equipement->imprimante->update($request->only([
                'type_imprimante_id', 'est_couleur', 'est_multifonction',
                'fonctions', 'adresse_ip', 'snmp_community',
            ]));

            // Historique technique
            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'TECHNIQUE',
                'motif' => 'Mise à jour des informations techniques',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Imprimante mise à jour avec succès.']);
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
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

        DB::transaction(function () use ($request, $id) {
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

        DB::transaction(function () use ($request, $id) {
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

        return response()->json(['success' => true, 'message' => 'Imprimante supprimée.']);
    }

    public function storeAffectation(Request $request)
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:EMPLOYE,POSTE,LOCAL',
        ]);

        DB::transaction(function () use ($request) {
            $equipement = Equipement::findOrFail($request->equipement_id);

            AffectationEquipement::where('equipement_id', $request->equipement_id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $direction_id = null;
            $service_id = null;

            if ($request->type_cible === 'EMPLOYE' && $request->dossier_employe_id) {
                $employe = Employe::find($request->dossier_employe_id);
                if ($employe) {
                    $direction_id = $employe->direction_id;
                    $service_id = $employe->service_id;
                }
            } elseif ($request->type_cible === 'POSTE' && $request->poste_travail_id) {
                $poste = PosteTravail::find($request->poste_travail_id);
                if ($poste) {
                    $direction_id = $poste->direction_id;
                    $service_id = $poste->service_id;
                }
            }

            AffectationEquipement::create([
                'code' => 'AFF-'.strtoupper(uniqid()),
                'equipement_id' => $request->equipement_id,
                'statut' => true,
                'type_cible' => $request->type_cible,
                'type_affectation' => 'PERMANENTE',
                'date_debut' => now(),
                'dossier_employe_id' => $request->dossier_employe_id,
                'poste_travail_id' => $request->poste_travail_id,
                'local_id' => $request->local_id,
                'direction_id' => $direction_id,
                'service_id' => $service_id,
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
                'motif' => 'Nouvelle affectation',
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    public function searchEmployes(Request $request)
    {
        $q = $request->get('q', '');

        return response()->json(
            Employe::where('est_actif', true)
                ->where(fn ($query) => $query
                    ->where('nom', 'ilike', "%{$q}%")
                    ->orWhere('prenom', 'ilike', "%{$q}%")
                    ->orWhere('matricule', 'ilike', "%{$q}%"))
                ->limit(20)->get()
                ->map(fn ($e) => ['id' => $e->id, 'text' => "{$e->nom} {$e->prenom} ({$e->matricule})", 'nom_complet' => $e->nom_complet])
        );
    }

    public function searchPostes(Request $request)
    {
        $q = $request->get('q', '');

        return response()->json(
            PosteTravail::with(['service', 'local'])
                ->where('actif', true)
                ->where(fn ($query) => $query
                    ->where('code', 'ilike', "%{$q}%")
                    ->orWhere('libelle', 'ilike', "%{$q}%"))
                ->limit(20)->get()
                ->map(fn ($p) => [
                    'id' => $p->id,
                    'text' => "{$p->code} — {$p->libelle}",
                    'code' => $p->code,
                    'libelle' => $p->libelle,
                    'service' => $p->service?->libelle,
                    'local' => $p->local?->libelle,
                ])
        );
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
                ->map(fn ($l) => ['id' => $l->id, 'text' => $l->nom_complet])
        );
    }

    public function storeMarque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $marque]);
    }

    public function storeTypeImprimante(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_imprimantes,libelle']);
        $type = TypeImprimante::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $type]);
    }

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = match ($aff->type_cible) {
                'EMPLOYE' => $aff->employe?->nom_complet ?? '—',
                'POSTE' => $aff->posteTravail?->code ?? '—',
                'LOCAL' => $aff->local?->libelle ?? '—',
                default => '—',
            };
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'type_imprimante' => $e->imprimante?->typeImprimante?->libelle ?? '—',
            'adresse_ip' => $e->imprimante?->adresse_ip ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
