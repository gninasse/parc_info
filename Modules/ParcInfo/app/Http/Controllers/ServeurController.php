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
use Modules\ParcInfo\Models\Serveur;
use Modules\ParcInfo\Models\TypeCpu;
use Modules\ParcInfo\Models\TypeDisque;
use Modules\ParcInfo\Models\TypeOs;
use Modules\ParcInfo\Models\TypeRam;

class ServeurController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesOs = TypeOs::orderBy('libelle')->get(['id', 'libelle']);
        $typesRam = TypeRam::orderBy('libelle')->get(['id', 'libelle']);
        $typesCpu = TypeCpu::orderBy('libelle')->get(['id', 'libelle']);
        $typesDisque = TypeDisque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.serveurs.index', compact(
            'sites', 'directions', 'marques', 'typesOs', 'typesRam', 'typesCpu', 'typesDisque'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'serveur.typeOs', 'serveur.serveurHote.equipement', 'affectationActive.local', 'affectationActive.service'])
            ->whereHas('serveur');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_serveur')) {
            $query->whereHas('serveur', fn ($q) => $q->where('type_serveur', $request->type_serveur));
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
                ->orWhereHas('serveur', fn ($q2) => $q2->where('nom_hote', 'ilike', "%{$s}%"))
                ->orWhereHas('serveur', fn ($q2) => $q2->where('adresse_ip', 'ilike', "%{$s}%"))
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
            $request->merge(['code_inventaire' => 'SRV-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            // Serveur
            'type_serveur' => 'required|in:Physique,Virtuel',
            'ram_capacite_go' => 'nullable|integer',
            'stockage_capacite_go' => 'nullable|integer',
            'os_type_id' => 'nullable|exists:parc_info_types_os,id',
            'ram_type_id' => 'nullable|exists:parc_info_types_rams,id',
            'cpu_type_id' => 'nullable|exists:parc_info_types_cpus,id',
            'serveur_hote_id' => 'nullable|exists:parc_info_serveurs,equipement_id',
        ]);

        $equipementId = \DB::transaction(function () use ($request) {
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

            Serveur::create([
                'equipement_id' => $equipement->id,
                'type_serveur' => $request->type_serveur,
                'role_serveur' => $request->role_serveur,
                'ram_type_id' => $request->ram_type_id,
                'ram_capacite_go' => $request->ram_capacite_go,
                'cpu_type_id' => $request->cpu_type_id,
                'nb_processeurs' => $request->nb_processeurs,
                'nb_coeurs_total' => $request->nb_coeurs_total,
                'disque_type_id' => $request->disque_type_id,
                'stockage_capacite_go' => $request->stockage_capacite_go,
                'os_type_id' => $request->os_type_id,
                'nom_hote' => $request->nom_hote,
                'domaine' => $request->domaine,
                'adresse_ip' => $request->adresse_ip,
                'adresse_mac' => $request->adresse_mac,
                'hyperviseur' => $request->hyperviseur,
                'serveur_hote_id' => $request->serveur_hote_id,
                'u_position_depart' => $request->u_position_depart,
                'u_position_fin' => $request->u_position_fin,
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
                    'service_id' => $request->service_id_aff,
                    'direction_id' => $request->direction_id_aff,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Serveur enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'serveur.typeOs', 'serveur.typeRam', 'serveur.typeCpu', 'serveur.typeDisque', 'serveur.serveurHote.equipement', 'serveur.vms.equipement',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service',
            'affectations.local', 'affectations.service',
            'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesOs = TypeOs::orderBy('libelle')->get(['id', 'libelle']);
        $typesRam = TypeRam::orderBy('libelle')->get(['id', 'libelle']);
        $typesCpu = TypeCpu::orderBy('libelle')->get(['id', 'libelle']);
        $typesDisque = TypeDisque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        $serveursHotes = Serveur::where('type_serveur', 'Physique')->with('equipement')->get();

        return view('parcinfo::informatique.serveurs.show', compact(
            'equipement', 'marques', 'typesOs', 'typesRam', 'typesCpu', 'typesDisque', 'sites', 'directions', 'serveursHotes'
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
                'date_acquisition', 'date_mise_en_service', 'date_fin_garantie',
                'valeur_achat', 'statut', 'etat',
            ]));

            $equipement->serveur->update($request->only([
                'type_serveur', 'role_serveur', 'ram_type_id', 'ram_capacite_go',
                'cpu_type_id', 'nb_processeurs', 'nb_coeurs_total',
                'disque_type_id', 'stockage_capacite_go', 'os_type_id',
                'nom_hote', 'domaine', 'adresse_ip', 'adresse_mac',
                'hyperviseur', 'serveur_hote_id', 'u_position_depart', 'u_position_fin',
            ]));
        });

        return response()->json(['success' => true, 'message' => 'Serveur mis à jour avec succès.']);
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

        return response()->json(['success' => true, 'message' => 'Serveur supprimé.']);
    }

    public function searchHotes(Request $request)
    {
        $q = $request->get('q', '');

        return response()->json(
            Serveur::where('type_serveur', 'Physique')
                ->whereHas('equipement', function ($query) use ($q) {
                    $query->where('code_inventaire', 'ilike', "%{$q}%")
                        ->orWhere('modele', 'ilike', "%{$q}%");
                })
                ->with('equipement')
                ->limit(20)->get()
                ->map(fn ($s) => [
                    'id' => $s->equipement_id,
                    'text' => "{$s->equipement->code_inventaire} — {$s->equipement->modele} (".($s->nom_hote ?? 'Sans nom').')',
                ])
        );
    }

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = $aff->local?->libelle ?? $aff->service?->libelle ?? '—';
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'type_serveur' => $e->serveur->type_serveur,
            'nom_ip' => ($e->serveur->nom_hote ?? '—').' / '.($e->serveur->adresse_ip ?? '—'),
            'os' => $e->serveur->typeOs?->libelle ?? '—',
            'config' => implode(' / ', array_filter([
                $e->serveur->nb_processeurs ? $e->serveur->nb_processeurs.' CPU' : null,
                $e->serveur->ram_capacite_go ? $e->serveur->ram_capacite_go.'Go RAM' : null,
                $e->serveur->stockage_capacite_go ? $e->serveur->stockage_capacite_go.'Go' : null,
            ])) ?: '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'hote' => $e->serveur->serveurHote?->equipement?->code_inventaire ?? '—',
        ];
    }
}
