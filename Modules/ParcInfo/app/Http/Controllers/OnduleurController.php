<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Infrastructure;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeInfrastructure;

class OnduleurController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.onduleurs.index', compact(
            'sites', 'directions', 'marques'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'infrastructure.typeInfrastructure', 'affectationActive.employe', 'affectationActive.posteTravail', 'affectationActive.local'])
            ->whereHas('infrastructure', function ($q) {
                $q->whereHas('typeInfrastructure', function ($t) {
                    $t->where('libelle', 'ilike', '%onduleur%')
                        ->orWhere('libelle', 'ilike', '%ups%');
                });
            });

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id))
                ->orWhereHas('affectationActive.posteTravail.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
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
            $request->merge(['code_inventaire' => 'OND-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'puissance_va' => 'nullable|integer|min:0',
            'autonomie_minutes' => 'nullable|integer|min:0',
            'date_dernier_remplacement_batterie' => 'nullable|date',
            'est_redondant' => 'nullable|boolean',
            'type_cible' => 'nullable|in:EMPLOYE,POSTE,LOCAL',
            'skip_affectation' => 'nullable|boolean',
        ]);

        $typeInfra = TypeInfrastructure::firstOrCreate(['libelle' => 'UPS']);

        $equipementId = \DB::transaction(function () use ($request, $typeInfra) {
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
                'etat' => $request->etat ?? 'bon',
                'tags' => $request->tags ? explode(',', $request->tags) : null,
            ]);

            Infrastructure::create([
                'equipement_id' => $equipement->id,
                'type_infra_id' => $typeInfra->id,
                'puissance_va' => $request->puissance_va,
                'autonomie_minutes' => $request->autonomie_minutes,
                'date_dernier_remplacement_batterie' => $request->date_dernier_remplacement_batterie,
                'est_redondant' => $request->boolean('est_redondant'),
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible')) {
                $niveau_rattachement = null;
                $direction_id = null;
                $service_id = null;
                $unite_id = null;

                if ($request->type_cible === 'EMPLOYE' && $request->dossier_employe_id) {
                    $employe = Employe::find($request->dossier_employe_id);
                    if ($employe) {
                        $niveau_rattachement = $employe->niveau_rattachement;
                        $direction_id = $employe->direction_id;
                        $service_id = $employe->service_id;
                        $unite_id = $employe->unite_id;
                    }
                } elseif ($request->type_cible === 'POSTE' && $request->poste_travail_id) {
                    $poste = PosteTravail::find($request->poste_travail_id);
                    if ($poste) {
                        $niveau_rattachement = $poste->niveau_rattachement;
                        $direction_id = $poste->direction_id;
                        $service_id = $poste->service_id;
                        $unite_id = $poste->unite_id;
                    }
                }

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
                    'niveau_rattachement' => $niveau_rattachement,
                    'direction_id' => $direction_id,
                    'service_id' => $service_id,
                    'unite_id' => $unite_id,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Onduleur enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque', 'infrastructure.typeInfrastructure',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
            'affectations.employe', 'affectations.posteTravail', 'affectations.local',
            'affectations.direction', 'affectations.service', 'affectations.unite', 'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.onduleurs.show', compact(
            'equipement', 'marques', 'sites', 'directions'
        ));
    }

    public function showJson($id)
    {
        $e = Equipement::with([
            'marque', 'infrastructure.typeInfrastructure',
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
            'puissance_va' => 'nullable|integer|min:0',
            'autonomie_minutes' => 'nullable|integer|min:0',
            'date_dernier_remplacement_batterie' => 'nullable|date',
            'est_redondant' => 'nullable|boolean',
        ]);

        $typeInfra = TypeInfrastructure::firstOrCreate(['libelle' => 'UPS']);

        \DB::transaction(function () use ($request, $id, $typeInfra) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'date_mise_en_service', 'date_fin_garantie',
                'valeur_achat', 'statut', 'etat',
            ]));

            $equipement->infrastructure->update([
                'type_infra_id' => $typeInfra->id,
                'puissance_va' => $request->puissance_va,
                'autonomie_minutes' => $request->autonomie_minutes,
                'date_dernier_remplacement_batterie' => $request->date_dernier_remplacement_batterie,
                'est_redondant' => $request->boolean('est_redondant'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Onduleur mis à jour avec succès.']);
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

        return response()->json(['success' => true, 'message' => 'Onduleur supprimé.']);
    }

    public function storeAffectation(Request $request)
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:EMPLOYE,POSTE,LOCAL',
        ]);

        \DB::transaction(function () use ($request) {
            $equipement = Equipement::findOrFail($request->equipement_id);

            AffectationEquipement::where('equipement_id', $request->equipement_id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $niveau_rattachement = null;
            $direction_id = null;
            $service_id = null;
            $unite_id = null;

            if ($request->type_cible === 'EMPLOYE' && $request->dossier_employe_id) {
                $employe = Employe::find($request->dossier_employe_id);
                if ($employe) {
                    $niveau_rattachement = $employe->niveau_rattachement;
                    $direction_id = $employe->direction_id;
                    $service_id = $employe->service_id;
                    $unite_id = $employe->unite_id;
                }
            } elseif ($request->type_cible === 'POSTE' && $request->poste_travail_id) {
                $poste = PosteTravail::find($request->poste_travail_id);
                if ($poste) {
                    $niveau_rattachement = $poste->niveau_rattachement;
                    $direction_id = $poste->direction_id;
                    $service_id = $poste->service_id;
                    $unite_id = $poste->unite_id;
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
                'niveau_rattachement' => $niveau_rattachement,
                'direction_id' => $direction_id,
                'service_id' => $service_id,
                'unite_id' => $unite_id,
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

    public function searchEmployes(Request $request)
    {
        $q = $request->get('q', '');

        return response()->json(
            Employe::where('est_actif', true)
                ->where(fn ($query) => $query
                    ->where('nom', 'ilike', "%{$q}%")
                    ->orWhere('prenom', 'ilike', "%{$q}%")
                    ->orWhere('matricule', 'ilike', "%{$q}%"))
                ->limit(20)->get(['id', 'matricule', 'nom', 'prenom'])
                ->map(fn ($e) => ['id' => $e->id, 'text' => "{$e->nom} {$e->prenom} ({$e->matricule})", 'matricule' => $e->matricule, 'nom' => $e->nom, 'prenom' => $e->prenom])
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
                ->map(fn ($l) => [
                    'id' => $l->id,
                    'text' => $l->nom_complet,
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
            'puissance_va' => $e->infrastructure->puissance_va ?? '—',
            'autonomie_minutes' => $e->infrastructure->autonomie_minutes ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
