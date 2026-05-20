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
use Modules\ParcInfo\Models\EquipementReseau;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeReseau;

class EquipementReseauController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseau = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.equipements-reseau.index', compact(
            'sites', 'directions', 'marques', 'typesReseau'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with([
                'marque',
                'equipementReseau.typeReseau',
                'affectationActive.employe',
                'affectationActive.posteTravail',
                'affectationActive.local',
            ])
            ->whereHas('equipementReseau');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.posteTravail.local.etage.batiment', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            })->orWhereHas('affectationActive.local.etage.batiment', function ($q) use ($request) {
                $q->where('site_id', $request->site_id);
            });
        }
        if ($request->filled('direction_id')) {
            $query->whereHas('affectationActive', function ($q) use ($request) {
                $q->where('direction_id', $request->direction_id);
            });
        }
        if ($request->filled('type_reseau_id')) {
            $query->whereHas('equipementReseau', function ($q) use ($request) {
                $q->where('type_reseau_id', $request->type_reseau_id);
            });
        }
        if ($request->filled('vitesse_port')) {
            $query->whereHas('equipementReseau', function ($q) use ($request) {
                $q->where('vitesse_port', $request->vitesse_port);
            });
        }

        if ($request->filled('search')) {
            $search = strtolower($request->search);
            $query->where(function ($q) use ($search) {
                $q->whereRaw('LOWER(code_inventaire) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(numero_serie) LIKE ?', ["%{$search}%"])
                    ->orWhereRaw('LOWER(modele) LIKE ?', ["%{$search}%"])
                    ->orWhereHas('marque', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(libelle) LIKE ?', ["%{$search}%"]);
                    })
                    ->orWhereHas('equipementReseau', function ($q2) use ($search) {
                        $q2->whereRaw('LOWER(adresse_ip_management) LIKE ?', ["%{$search}%"]);
                    });
            });
        }

        $total = $query->count();
        $offset = $request->get('offset', 0);
        $limit = $request->get('limit', 25);
        $equipements = $query->offset($offset)->limit($limit)->get();

        $rows = $equipements->map(function ($e) {
            return $this->formatRow($e);
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }

    private function formatRow(Equipement $e): array
    {
        $er = $e->equipementReseau;

        $affectationLabel = '—';
        if ($e->affectationActive) {
            if ($e->affectationActive->employe) {
                $affectationLabel = $e->affectationActive->employe->nom_complet;
            } elseif ($e->affectationActive->posteTravail) {
                $affectationLabel = $e->affectationActive->posteTravail->nom;
            } elseif ($e->affectationActive->local) {
                $affectationLabel = $e->affectationActive->local->nom;
            }
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque->libelle ?? '—').' '.$e->modele,
            'type_reseau' => $er->typeReseau->libelle ?? '—',
            'adresse_ip_management' => $er->adresse_ip_management ?? '—',
            'nombre_ports' => $er->nombre_ports ?? null,
            'vitesse_port' => $er->vitesse_port ?? null,
            'statut' => $e->statut,
            'statut_label' => ucfirst(str_replace('_', ' ', $e->statut)),
            'affectation' => $affectationLabel,
            'etat' => $e->etat,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'type_reseau_id' => 'nullable|exists:parc_info_types_reseau,id',
            'nombre_ports' => 'nullable|integer|min:1|max:400',
            'vitesse_port' => 'nullable|string|in:100Mbps,1Gbps,10Gbps,25Gbps,40Gbps,100Gbps',
            'support_poe' => 'nullable|boolean',
            'poe_budget_watts' => 'nullable|integer|min:0',
            'support_vlan' => 'nullable|boolean',
            'support_stp' => 'nullable|boolean',
            'support_lacp' => 'nullable|boolean',
            'support_snmp' => 'nullable|boolean',
            'adresse_ip_management' => 'nullable|ip',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_version' => 'nullable|string|in:v1,v2c,v3',
            'firmware_version' => 'nullable|string|max:100',
            'vlans_configures' => 'nullable|string',
            'nombre_ports_uplink' => 'nullable|integer|min:0',
            'support_redundance' => 'nullable|boolean',
            'location_detail' => 'nullable|string|max:255',
            'type_cible' => 'nullable|in:EMPLOYE,POSTE,LOCAL',
            'skip_affectation' => 'nullable|boolean',
        ]);

        return DB::transaction(function () use ($request) {
            $codeInventaire = $request->code_inventaire;
            if (! $codeInventaire) {
                $lastId = Equipement::max('id') ?? 0;
                $nextId = $lastId + 1;
                $codeInventaire = 'NET-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            $equipement = Equipement::create([
                'code_inventaire' => $codeInventaire,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie' => $request->date_fin_garantie,
                'valeur_achat' => $request->valeur_achat,
                'statut' => $request->statut,
                'etat' => $request->etat,
                'tags' => $request->tags,
            ]);

            EquipementReseau::create([
                'equipement_id' => $equipement->id,
                'type_reseau_id' => $request->type_reseau_id,
                'nombre_ports' => $request->nombre_ports,
                'vitesse_port' => $request->vitesse_port,
                'support_poe' => $request->boolean('support_poe'),
                'poe_budget_watts' => $request->poe_budget_watts,
                'support_vlan' => $request->boolean('support_vlan'),
                'support_stp' => $request->boolean('support_stp'),
                'support_lacp' => $request->boolean('support_lacp'),
                'support_snmp' => $request->boolean('support_snmp'),
                'firmware_version' => $request->firmware_version,
                'adresse_ip_management' => $request->adresse_ip_management,
                'snmp_community' => $request->snmp_community,
                'snmp_version' => $request->snmp_version,
                'vlans_configures' => $request->vlans_configures,
                'modele_reference' => $request->modele_reference,
                'nombre_ports_uplink' => $request->nombre_ports_uplink,
                'support_redundance' => $request->boolean('support_redundance'),
                'location_detail' => $request->location_detail,
            ]);

            if (! $request->boolean('skip_affectation') && $request->filled('type_cible') && $request->statut === 'en_service') {
                $this->createAffectation($request, $equipement);
            }

            return response()->json([
                'success' => true,
                'message' => 'Équipement réseau créé avec succès',
                'equipement_id' => $equipement->id,
            ]);
        });
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'equipementReseau.typeReseau',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction',
            'affectationActive.service',
            'affectations.employe',
            'affectations.posteTravail',
            'affectations.local',
            'affectations.direction',
            'historique' => fn ($q) => $q->orderByDesc('date_changement'),
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseau = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.equipements-reseau.show', compact(
            'equipement', 'marques', 'typesReseau', 'sites', 'directions'
        ));
    }

    public function showJson($id)
    {
        $equipement = Equipement::with('equipementReseau', 'affectationActive')->findOrFail($id);

        return response()->json($equipement);
    }

    public function update(Request $request, $id)
    {
        $equipement = Equipement::findOrFail($id);

        $request->validate([
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie,'.$equipement->id,
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'type_reseau_id' => 'nullable|exists:parc_info_types_reseau,id',
            'nombre_ports' => 'nullable|integer|min:1|max:400',
            'vitesse_port' => 'nullable|string|in:100Mbps,1Gbps,10Gbps,25Gbps,40Gbps,100Gbps',
            'support_poe' => 'nullable|boolean',
            'poe_budget_watts' => 'nullable|integer|min:0',
            'support_vlan' => 'nullable|boolean',
            'support_stp' => 'nullable|boolean',
            'support_lacp' => 'nullable|boolean',
            'support_snmp' => 'nullable|boolean',
            'adresse_ip_management' => 'nullable|ip',
            'snmp_community' => 'nullable|string|max:255',
            'snmp_version' => 'nullable|string|in:v1,v2c,v3',
            'firmware_version' => 'nullable|string|max:100',
            'vlans_configures' => 'nullable|string',
            'nombre_ports_uplink' => 'nullable|integer|min:0',
            'support_redundance' => 'nullable|boolean',
            'location_detail' => 'nullable|string|max:255',
        ]);

        DB::transaction(function () use ($request, $equipement) {
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
                'tags' => $request->tags,
            ]);

            $equipement->equipementReseau->update([
                'type_reseau_id' => $request->type_reseau_id,
                'nombre_ports' => $request->nombre_ports,
                'vitesse_port' => $request->vitesse_port,
                'support_poe' => $request->boolean('support_poe'),
                'poe_budget_watts' => $request->poe_budget_watts,
                'support_vlan' => $request->boolean('support_vlan'),
                'support_stp' => $request->boolean('support_stp'),
                'support_lacp' => $request->boolean('support_lacp'),
                'support_snmp' => $request->boolean('support_snmp'),
                'firmware_version' => $request->firmware_version,
                'adresse_ip_management' => $request->adresse_ip_management,
                'snmp_community' => $request->snmp_community,
                'snmp_version' => $request->snmp_version,
                'vlans_configures' => $request->vlans_configures,
                'modele_reference' => $request->modele_reference,
                'nombre_ports_uplink' => $request->nombre_ports_uplink,
                'support_redundance' => $request->boolean('support_redundance'),
                'location_detail' => $request->location_detail,
            ]);

            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'TECHNIQUE',
                'motif' => 'Mise à jour des caractéristiques',
                'date_changement' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Équipement mis à jour']);
    }

    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'motif' => 'required|string',
        ]);

        $equipement = Equipement::findOrFail($id);

        DB::transaction(function () use ($request, $equipement) {
            $ancienStatut = $equipement->statut;
            $nouveauStatut = $request->statut;

            if ($nouveauStatut === 'en_stock' && $equipement->affectationActive) {
                $equipement->affectationActive->update([
                    'statut' => false,
                    'date_fin' => now(),
                ]);
            }

            $equipement->update(['statut' => $nouveauStatut]);

            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => $nouveauStatut,
                'motif' => $request->motif,
                'date_changement' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Statut mis à jour']);
    }

    public function updateEtat(Request $request, $id)
    {
        $request->validate([
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'motif' => 'required|string',
        ]);

        $equipement = Equipement::findOrFail($id);

        DB::transaction(function () use ($request, $equipement) {
            $ancienEtat = $equipement->etat;
            $nouvelEtat = $request->etat;

            $equipement->update(['etat' => $nouvelEtat]);

            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'ETAT',
                'ancien_etat' => $ancienEtat,
                'nouvel_etat' => $nouvelEtat,
                'motif' => $request->motif,
                'date_changement' => now(),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'État mis à jour']);
    }

    public function desaffecter(Request $request, $id)
    {
        $equipement = Equipement::findOrFail($id);

        DB::transaction(function () use ($equipement) {
            if ($equipement->affectationActive) {
                $equipement->affectationActive->update([
                    'statut' => false,
                    'date_fin' => now(),
                ]);

                HistoriqueChangement::create([
                    'equipement_id' => $equipement->id,
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'AFFECTATION',
                    'motif' => 'Désaffectation',
                    'date_changement' => now(),
                ]);
            }

            $ancienStatut = $equipement->statut;
            $equipement->update(['statut' => 'en_stock']);

            if ($ancienStatut !== 'en_stock') {
                HistoriqueChangement::create([
                    'equipement_id' => $equipement->id,
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'STATUT',
                    'ancien_statut' => $ancienStatut,
                    'nouveau_statut' => 'en_stock',
                    'motif' => 'Retour en stock suite à désaffectation',
                    'date_changement' => now(),
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Équipement désaffecté et retourné en stock']);
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Équipement supprimé']);
    }

    public function storeAffectation(Request $request)
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:EMPLOYE,POSTE,LOCAL',
            'dossier_employe_id' => 'required_if:type_cible,EMPLOYE|nullable|exists:grh_dossiers_employes,id',
            'poste_travail_id' => 'required_if:type_cible,POSTE|nullable|exists:organisation_postes_travail,id',
            'local_id' => 'required_if:type_cible,LOCAL|nullable|exists:organisation_locaux,id',
        ]);

        $equipement = Equipement::findOrFail($request->equipement_id);

        DB::transaction(function () use ($request, $equipement) {
            $this->createAffectation($request, $equipement);
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée']);
    }

    private function createAffectation(Request $request, Equipement $equipement)
    {
        if ($equipement->affectationActive) {
            $equipement->affectationActive->update([
                'statut' => false,
                'date_fin' => now(),
            ]);
        }

        $niveau_rattachement = null;
        $direction_id = null;
        $service_id = null;

        if ($request->type_cible === 'EMPLOYE' && $request->filled('dossier_employe_id')) {
            $employe = Employe::find($request->dossier_employe_id);
            if ($employe) {
                $niveau_rattachement = $employe->niveau_rattachement;
                $direction_id = $employe->direction_id;
                $service_id = $employe->service_id;
            }
        } elseif ($request->type_cible === 'POSTE' && $request->filled('poste_travail_id')) {
            $poste = PosteTravail::find($request->poste_travail_id);
            if ($poste) {
                $niveau_rattachement = $poste->niveau_rattachement;
                $direction_id = $poste->direction_id;
                $service_id = $poste->service_id;
            }
        }

        AffectationEquipement::create([
            'equipement_id' => $equipement->id,
            'type_affectation' => 'PERMANENTE',
            'dossier_employe_id' => $request->dossier_employe_id,
            'poste_travail_id' => $request->poste_travail_id,
            'local_id' => $request->local_id,
            'niveau_rattachement' => $niveau_rattachement,
            'direction_id' => $direction_id,
            'service_id' => $service_id,
            'date_debut' => now(),
            'statut' => true,
            'cree_par' => auth()->id(),
        ]);

        HistoriqueChangement::create([
            'equipement_id' => $equipement->id,
            'utilisateur_id' => auth()->id(),
            'type_changement' => 'AFFECTATION',
            'motif' => 'Nouvelle affectation',
            'date_changement' => now(),
        ]);

        if ($equipement->statut === 'en_stock') {
            $equipement->update(['statut' => 'en_service']);
            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => 'en_stock',
                'nouveau_statut' => 'en_service',
                'motif' => 'Mise en service automatique suite à affectation',
                'date_changement' => now(),
            ]);
        }
    }

    public function searchEmployes(Request $request)
    {
        $q = $request->get('q', '');
        $employes = Employe::where('nom', 'ilike', "%$q%")
            ->orWhere('prenom', 'ilike', "%$q%")
            ->orWhere('matricule', 'ilike', "%$q%")
            ->limit(20)
            ->get()
            ->map(function ($e) {
                return [
                    'id' => $e->id,
                    'text' => "{$e->matricule} - {$e->nom_complet}",
                    'sub' => $e->fonction ?? 'Aucune fonction',
                ];
            });

        return response()->json($employes);
    }

    public function searchPostes(Request $request)
    {
        $q = $request->get('q', '');
        $postes = PosteTravail::with('direction', 'service')
            ->where('nom', 'ilike', "%$q%")
            ->limit(20)
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'text' => $p->nom,
                    'sub' => $p->direction->libelle ?? 'Aucune direction',
                ];
            });

        return response()->json($postes);
    }

    public function searchLocaux(Request $request)
    {
        $q = $request->get('q', '');
        $locaux = Local::with('etage.batiment')
            ->where('nom', 'ilike', "%$q%")
            ->limit(20)
            ->get()
            ->map(function ($l) {
                return [
                    'id' => $l->id,
                    'text' => $l->nom,
                    'sub' => ($l->etage->batiment->nom ?? '').' - '.($l->etage->nom ?? ''),
                ];
            });

        return response()->json($locaux);
    }

    public function storeMarque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'marque' => $marque]);
    }

    public function storeTypeReseau(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_reseau,libelle']);
        $type = TypeReseau::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'type' => $type]);
    }
}
