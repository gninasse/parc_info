<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\EquipementReseau;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeReseau;

class PareFeuController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);

        $pageTitle = 'Pare-feux (Firewalls)';
        $dataUrl = route('parc-info.parefeux.data');
        $routePrefix = 'parc-info.parefeux';

        return view('parcinfo::informatique.reseaux.index', compact(
            'sites', 'directions', 'marques', 'typesReseaux', 'pageTitle', 'dataUrl', 'routePrefix'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'reseau.typeReseau', 'affectationActive.local.etage.batiment'])
            ->whereHas('reseau', function ($q) {
                $q->whereHas('typeReseau', function ($t) {
                    $t->where('libelle', 'ilike', '%pare-feu%')->orWhere('libelle', 'ilike', '%firewall%');
                });
            });

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_reseau_id')) {
            $query->whereHas('reseau', fn ($q) => $q->where('type_reseau_id', $request->type_reseau_id));
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
                ->orWhereHas('reseau', fn ($q2) => $q2->where('adresse_ip', 'ilike', "%{$s}%"))
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
            $request->merge(['code_inventaire' => 'NET-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            // Reseau
            'type_reseau_id' => 'nullable|exists:parc_info_types_reseaux,id',
            'nb_ports' => 'nullable|integer',
            'vitesse_max_mbps' => 'nullable|integer',
            'est_poe' => 'nullable|boolean',
            'version_firmware' => 'nullable|string',
            'adresse_ip' => 'nullable|ip',
            'masque_sous_reseau' => 'nullable|ip',
            'passerelle' => 'nullable|ip',
            'est_manageable' => 'nullable|boolean',
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

            EquipementReseau::create([
                'equipement_id' => $equipement->id,
                'type_reseau_id' => $request->type_reseau_id,
                'nb_ports' => $request->nb_ports,
                'vitesse_max_mbps' => $request->vitesse_max_mbps,
                'est_poe' => $request->boolean('est_poe'),
                'version_firmware' => $request->version_firmware,
                'adresse_ip' => $request->adresse_ip,
                'masque_sous_reseau' => $request->masque_sous_reseau,
                'passerelle' => $request->passerelle,
                'est_manageable' => $request->boolean('est_manageable', true),
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

        return response()->json(['success' => true, 'message' => 'Équipement réseau enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'reseau.typeReseau',
            'affectationActive.local.etage.batiment.site',
            'affectations.local',
            'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        $routePrefix = 'parc-info.parefeux';

        return view('parcinfo::informatique.reseaux.show', compact(
            'equipement', 'marques', 'typesReseaux', 'sites', 'directions', 'routePrefix'
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
            'masque_sous_reseau' => 'nullable|ip',
            'passerelle' => 'nullable|ip',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'statut', 'etat',
            ]));

            $equipement->reseau->update([
                'type_reseau_id' => $request->type_reseau_id,
                'nb_ports' => $request->nb_ports,
                'vitesse_max_mbps' => $request->vitesse_max_mbps,
                'est_poe' => $request->boolean('est_poe'),
                'version_firmware' => $request->version_firmware,
                'adresse_ip' => $request->adresse_ip,
                'masque_sous_reseau' => $request->masque_sous_reseau,
                'passerelle' => $request->passerelle,
                'est_manageable' => $request->boolean('est_manageable'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Équipement mis à jour avec succès.']);
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

        return response()->json(['success' => true, 'message' => 'Équipement supprimé.']);
    }

    public function storeTypeReseau(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_reseaux,libelle']);
        $type = TypeReseau::create(['libelle' => $request->libelle]);

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
            'type_reseau' => $e->reseau->typeReseau?->libelle ?? '—',
            'adresse_ip' => $e->reseau->adresse_ip ?? '—',
            'nb_ports' => $e->reseau->nb_ports ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
        ];
    }
}
