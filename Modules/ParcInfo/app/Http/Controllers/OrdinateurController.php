<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Grh\Models\Employe;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Local;
use Modules\Organisation\Models\PosteTravail;
use Modules\Organisation\Models\Service;
use Modules\Organisation\Models\Site;
use Modules\Organisation\Models\Unite;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\Ordinateur;
use Modules\ParcInfo\Models\TypeCpu;
use Modules\ParcInfo\Models\TypeDisque;
use Modules\ParcInfo\Models\TypeOs;
use Modules\ParcInfo\Models\TypeRam;

class OrdinateurController extends Controller
{
    public function index()
    {
        $sites      = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques    = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesOs    = TypeOs::orderBy('libelle')->get(['id', 'libelle']);
        $typesRam   = TypeRam::orderBy('libelle')->get(['id', 'libelle']);
        $typesCpu   = TypeCpu::orderBy('libelle')->get(['id', 'libelle']);
        $typesDisque = TypeDisque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.ordinateurs.index', compact(
            'sites', 'directions', 'marques', 'typesOs', 'typesRam', 'typesCpu', 'typesDisque'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'ordinateur.typeOs', 'affectationActive.employe', 'affectationActive.posteTravail'])
            ->whereHas('ordinateur', fn ($q) => $q->where('type_pc', 'Fixe'));

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.posteTravail.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
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
                ->orWhereHas('ordinateur', fn ($q2) => $q2->where('nom_hote', 'ilike', "%{$s}%"))
            );
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows  = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        return response()->json([
            'total' => $total,
            'rows'  => $rows->map(fn ($e) => $this->formatRow($e)),
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie'    => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id'       => 'nullable|exists:parc_info_marques,id',
            'modele'          => 'required|string|max:255',
            'date_acquisition'=> 'required|date',
            'statut'          => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat'            => 'required|in:bon,passable,mauvais,avarie',
            // Ordinateur
            'ram_capacite_go'     => 'nullable|integer',
            'stockage_capacite_go'=> 'nullable|integer',
            'os_type_id'          => 'nullable|exists:parc_info_types_os,id',
            'ram_type_id'         => 'nullable|exists:parc_info_types_rams,id',
            'cpu_type_id'         => 'nullable|exists:parc_info_types_cpus,id',
            // Affectation
            'type_cible'          => 'nullable|in:EMPLOYE,POSTE,LOCAL',
            'date_debut'          => 'required_with:type_cible|nullable|date',
        ]);

        \DB::transaction(function () use ($request) {
            $equipement = Equipement::create([
                'code_inventaire'      => $request->code_inventaire,
                'numero_serie'         => $request->numero_serie,
                'marque_id'            => $request->marque_id,
                'modele'               => $request->modele,
                'date_acquisition'     => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie'    => $request->date_fin_garantie,
                'valeur_achat'         => $request->valeur_achat,
                'statut'               => $request->statut,
                'etat'                 => $request->etat ?? 'bon',
                'tags'                 => $request->tags ? explode(',', $request->tags) : null,
            ]);

            Ordinateur::create([
                'equipement_id'        => $equipement->id,
                'type_pc'              => 'Fixe',
                'ram_type_id'          => $request->ram_type_id,
                'ram_capacite_go'      => $request->ram_capacite_go,
                'cpu_type_id'          => $request->cpu_type_id,
                'processeur_model'     => $request->processeur_model,
                'disque_type_id'       => $request->disque_type_id,
                'stockage_capacite_go' => $request->stockage_capacite_go,
                'os_type_id'           => $request->os_type_id,
                'nom_hote'             => $request->nom_hote,
                'adresse_mac_ethernet' => $request->adresse_mac_ethernet,
                'adresse_mac_wifi'     => $request->adresse_mac_wifi,
                'domaine_workgroup'    => $request->domaine_workgroup,
                'support_tpm2'         => $request->boolean('support_tpm2'),
                'support_secure_boot'  => $request->boolean('support_secure_boot'),
                'licence_windows_type' => $request->licence_windows_type,
                'licence_windows_cle'  => $request->licence_windows_cle,
                'licence_office_type'  => $request->licence_office_type,
                'licence_office_cle'   => $request->licence_office_cle,
            ]);

            if ($request->filled('type_cible')) {
                AffectationEquipement::create([
                    'code'                => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id'       => $equipement->id,
                    'statut'              => true,
                    'type_cible'          => $request->type_cible,
                    'type_affectation'    => $request->type_affectation ?? 'PERMANENTE',
                    'date_debut'          => $request->date_debut,
                    'date_fin'            => $request->date_fin,
                    'dossier_employe_id'  => $request->dossier_employe_id,
                    'poste_travail_id'    => $request->poste_travail_id,
                    'local_id'            => $request->local_id,
                    'niveau_rattachement' => $request->niveau_rattachement,
                    'direction_id'        => $request->direction_id_aff,
                    'service_id'          => $request->service_id_aff,
                    'unite_id'            => $request->unite_id_aff,
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Ordinateur enregistré avec succès.']);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'ordinateur.typeOs', 'ordinateur.typeRam', 'ordinateur.typeCpu', 'ordinateur.typeDisque',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
            'affectations.employe',
            'affectations.posteTravail',
            'affectations.local',
            'affectations.direction', 'affectations.service', 'affectations.unite',
            'historique',
        ])->findOrFail($id);

        $marques     = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesOs     = TypeOs::orderBy('libelle')->get(['id', 'libelle']);
        $typesRam    = TypeRam::orderBy('libelle')->get(['id', 'libelle']);
        $typesCpu    = TypeCpu::orderBy('libelle')->get(['id', 'libelle']);
        $typesDisque = TypeDisque::orderBy('libelle')->get(['id', 'libelle']);
        $directions  = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.ordinateurs.show', compact(
            'equipement', 'marques', 'typesOs', 'typesRam', 'typesCpu', 'typesDisque', 'directions'
        ));
    }

    public function showJson($id)
    {
        $e = Equipement::with([
            'marque',
            'ordinateur.typeOs', 'ordinateur.typeRam', 'ordinateur.typeCpu', 'ordinateur.typeDisque',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $e]);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'code_inventaire' => "required|string|unique:parc_info_equipements,code_inventaire,{$id}",
            'numero_serie'    => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele'          => 'required|string|max:255',
            'date_acquisition'=> 'required|date',
            'statut'          => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat'            => 'required|in:bon,passable,mauvais,avarie',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'code_inventaire', 'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'date_mise_en_service', 'date_fin_garantie',
                'valeur_achat', 'statut', 'etat',
            ]));

            $equipement->ordinateur->update($request->only([
                'ram_type_id', 'ram_capacite_go', 'cpu_type_id', 'processeur_model',
                'disque_type_id', 'stockage_capacite_go', 'os_type_id',
                'nom_hote', 'adresse_mac_ethernet', 'adresse_mac_wifi', 'domaine_workgroup',
                'support_tpm2', 'support_secure_boot',
                'licence_windows_type', 'licence_windows_cle',
                'licence_office_type', 'licence_office_cle',
            ]));
        });

        return response()->json(['success' => true, 'message' => 'Ordinateur mis à jour avec succès.']);
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Ordinateur supprimé.']);
    }

    // ── AJAX helpers ──────────────────────────────────────────────────────────

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
                    'id'      => $p->id,
                    'text'    => "{$p->code} — {$p->libelle}",
                    'code'    => $p->code,
                    'libelle' => $p->libelle,
                    'service' => $p->service?->libelle,
                    'local'   => $p->local?->libelle,
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
                    'id'   => $l->id,
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

    public function storeTypeRam(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_rams,libelle']);
        $type = TypeRam::create(['libelle' => $request->libelle]);
        return response()->json(['success' => true, 'data' => $type]);
    }

    public function storeTypeOs(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_os,libelle']);
        $type = TypeOs::create(['libelle' => $request->libelle]);
        return response()->json(['success' => true, 'data' => $type]);
    }

    public function storeTypeDisque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_disques,libelle']);
        $type = TypeDisque::create(['libelle' => $request->libelle]);
        return response()->json(['success' => true, 'data' => $type]);
    }

    public function storeAffectation(Request $request)
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible'    => 'required|in:EMPLOYE,POSTE,LOCAL',
            'date_debut'    => 'required|date',
            'date_fin'      => 'nullable|date|after:date_debut',
        ]);

        // Clôturer l'affectation active précédente
        AffectationEquipement::where('equipement_id', $request->equipement_id)
            ->where('statut', true)
            ->update(['statut' => false, 'date_fin' => now()]);

        AffectationEquipement::create([
            'code'                => 'AFF-'.strtoupper(uniqid()),
            'equipement_id'       => $request->equipement_id,
            'statut'              => true,
            'type_cible'          => $request->type_cible,
            'type_affectation'    => $request->type_affectation ?? 'PERMANENTE',
            'date_debut'          => $request->date_debut,
            'date_fin'            => $request->date_fin,
            'dossier_employe_id'  => $request->dossier_employe_id,
            'poste_travail_id'    => $request->poste_travail_id,
            'local_id'            => $request->local_id,
            'niveau_rattachement' => $request->niveau_rattachement,
            'direction_id'        => $request->direction_id_aff,
            'service_id'          => $request->service_id_aff,
            'unite_id'            => $request->unite_id_aff,
        ]);

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    // ── Formatage ligne tableau ───────────────────────────────────────────────

    private function formatRow(Equipement $e): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = match ($aff->type_cible) {
                'EMPLOYE' => $aff->employe?->full_name ?? '—',
                'POSTE'   => $aff->posteTravail?->code ?? '—',
                'LOCAL'   => $aff->local?->libelle ?? '—',
                default   => '—',
            };
        }

        return [
            'id'              => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele'   => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'os'              => $e->ordinateur?->typeOs?->libelle ?? '—',
            'config'          => implode(' / ', array_filter([
                $e->ordinateur?->processeur_model,
                $e->ordinateur?->ram_capacite_go ? $e->ordinateur->ram_capacite_go.'Go RAM' : null,
                $e->ordinateur?->stockage_capacite_go ? $e->ordinateur->stockage_capacite_go.'Go' : null,
            ])) ?: '—',
            'statut'          => $e->statut,
            'statut_label'    => $e->statut_label,
            'affectation'     => $affLabel,
            'etat'            => $e->etat,
        ];
    }
}
