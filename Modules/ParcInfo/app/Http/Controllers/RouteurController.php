<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
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

class RouteurController extends Controller
{
    /**
     * Display the index view for Routeurs.
     */
    public function index(): View
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);

        $pageTitle = 'Routeurs Réseau';
        $dataUrl = route('parc-info.routeurs.data');
        $routePrefix = 'parc-info.routeurs';

        return view('parcinfo::informatique.routeurs.index', compact(
            'sites', 'directions', 'marques', 'typesReseaux', 'pageTitle', 'dataUrl', 'routePrefix'
        ));
    }

    /**
     * Get JSON data for Bootstrap Table.
     */
    public function getData(Request $request): JsonResponse
    {
        $query = Equipement::query()
            ->with(['marque', 'reseau.typeReseau', 'affectationActive.employe', 'affectationActive.posteTravail', 'affectationActive.local'])
            ->whereHas('reseau', function ($q) {
                $q->whereHas('typeReseau', function ($t) {
                    $t->where('libelle', 'ilike', '%routeur%');
                });
            });

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_reseau_id')) {
            $query->whereHas('reseau', fn ($q) => $q->where('type_reseau_id', $request->type_reseau_id));
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

    /**
     * Store a newly created Routeur.
     */
    public function store(Request $request): JsonResponse
    {
        $typeReseau = TypeReseau::firstOrCreate(['libelle' => 'Routeur']);
        $request->merge(['type_reseau_id' => $typeReseau->id]);

        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderBy('id', 'desc')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge(['code_inventaire' => 'NET-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        $request->validate([
            'code_inventaire' => 'nullable|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            // Reseau
            'type_reseau_id' => 'nullable|exists:parc_info_types_reseaux,id',
            'nb_ports' => 'nullable|integer|min:0',
            'vitesse_max_mbps' => 'nullable|integer',
            'version_firmware' => 'nullable|string',
            'adresse_ip' => 'nullable|ip',
            'masque_sous_reseau' => 'nullable|ip',
            'passerelle' => 'nullable|ip',
            'est_manageable' => 'nullable|boolean',
            'type_cible' => 'nullable|in:EMPLOYE,POSTE,LOCAL',
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

            EquipementReseau::create([
                'equipement_id' => $equipement->id,
                'type_reseau_id' => $request->type_reseau_id,
                'nb_ports' => $request->nb_ports,
                'vitesse_max_mbps' => $request->vitesse_max_mbps,
                'est_poe' => false,
                'version_firmware' => $request->version_firmware,
                'adresse_ip' => $request->adresse_ip,
                'masque_sous_reseau' => $request->masque_sous_reseau,
                'passerelle' => $request->passerelle,
                'est_manageable' => $request->boolean('est_manageable', true),
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

        return response()->json(['success' => true, 'message' => 'Routeur enregistré avec succès.', 'equipement_id' => $equipementId]);
    }

    /**
     * Show a detailed technical view for Routeur.
     */
    public function show($id): View
    {
        $equipement = Equipement::with([
            'marque',
            'reseau.typeReseau',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
            'affectations.employe', 'affectations.posteTravail', 'affectations.local',
            'affectations.direction', 'affectations.service', 'affectations.unite', 'historique',
        ])->findOrFail($id);

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        $routePrefix = 'parc-info.routeurs';

        return view('parcinfo::informatique.routeurs.show', compact(
            'equipement', 'marques', 'typesReseaux', 'sites', 'directions', 'routePrefix'
        ));
    }

    /**
     * Update an existing Routeur.
     */
    public function update(Request $request, $id): JsonResponse
    {
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'nb_ports' => 'nullable|integer|min:0',
            'vitesse_max_mbps' => 'nullable|integer',
            'version_firmware' => 'nullable|string',
            'adresse_ip' => 'nullable|ip',
            'masque_sous_reseau' => 'nullable|ip',
            'passerelle' => 'nullable|ip',
            'est_manageable' => 'nullable|boolean',
        ]);

        \DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $equipement->update($request->only([
                'numero_serie', 'marque_id', 'modele',
                'date_acquisition', 'statut', 'etat',
            ]));

            $equipement->reseau->update([
                'nb_ports' => $request->nb_ports,
                'vitesse_max_mbps' => $request->vitesse_max_mbps,
                'version_firmware' => $request->version_firmware,
                'adresse_ip' => $request->adresse_ip,
                'masque_sous_reseau' => $request->masque_sous_reseau,
                'passerelle' => $request->passerelle,
                'est_manageable' => $request->boolean('est_manageable'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Routeur mis à jour avec succès.']);
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

        return response()->json(['success' => true, 'message' => 'Routeur désaffecté et mis en stock.']);
    }

    /**
     * Delete an existing Router.
     */
    public function destroy($id): JsonResponse
    {
        Equipement::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Routeur supprimé.']);
    }

    /**
     * Create network type dynamically.
     */
    public function storeTypeReseau(Request $request): JsonResponse
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_types_reseaux,libelle']);
        $type = TypeReseau::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $type]);
    }

    /**
     * Create an allocation manually.
     */
    public function storeAffectation(Request $request): JsonResponse
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

    /**
     * AJAX search for active employees.
     */
    public function searchEmployes(Request $request): JsonResponse
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

    /**
     * AJAX search for active workstations.
     */
    public function searchPostes(Request $request): JsonResponse
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
            'type_reseau' => $e->reseau->typeReseau?->libelle ?? '—',
            'adresse_ip' => $e->reseau->adresse_ip ?? '—',
            'nb_ports' => $e->reseau->nb_ports ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
