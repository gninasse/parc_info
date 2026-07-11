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
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\ParcInfo\Models\ChampConfig;
use Modules\ParcInfo\Models\Dictionnaire;
use Modules\ParcInfo\Models\DictionnaireValeur;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Marque;

class EquipementDynamiqueController extends Controller
{
    /**
     * Display a listing of the resource for a category.
     */
    public function index(Request $request)
    {
        $categoryCode = $request->route()->defaults['category'] ?? $request->get('category');
        $category = CategorieEquipement::where('code', $categoryCode)->firstOrFail();

        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        // Load columns to display in index
        $colonnesConfig = ChampConfig::where('categorie_id', $category->id)
            ->where('afficher_dans_liste', true)
            ->orderBy('ordre_colonne_liste')
            ->get();

        // Load fields to display in wizard/modal
        $champsConfig = ChampConfig::where('categorie_id', $category->id)
            ->where('afficher_dans_modal', true)
            ->orderBy('ordre_affichage')
            ->get();

        return view('parcinfo::informatique.index', compact(
            'category', 'sites', 'directions', 'marques', 'colonnesConfig', 'champsConfig'
        ));
    }

    /**
     * Fetch paginated list data for bootstrap-table.
     */
    public function getData(Request $request)
    {
        $categoryCode = $request->route()->defaults['category'] ?? $request->get('category');
        $category = CategorieEquipement::where('code', $categoryCode)->firstOrFail();

        $query = Equipement::query()
            ->with([
                'marque',
                'affectationActive.employe',
                'affectationActive.posteTravail',
                'affectationActive.local',
                'affectationActive.direction',
                'affectationActive.service',
                'affectationActive.unite',
            ])
            ->where('categorie_id', $category->id);

        // Apply filters
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

        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', 'ilike', "%{$s}%")
                ->orWhere('numero_serie', 'ilike', "%{$s}%")
                ->orWhere('modele', 'ilike', "%{$s}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', 'ilike', "%{$s}%"))
                ->orWhere('champs_valeurs::text', 'ilike', "%{$s}%") // simple PostgreSQL JSONB search
            );
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        $total = $query->count();
        $rows = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        // Get index columns to resolve dynamic lookup text
        $champsListe = ChampConfig::where('categorie_id', $category->id)
            ->where('afficher_dans_liste', true)
            ->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(fn ($e) => $this->formatRow($e, $champsListe)),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $categoryCode = $request->route()->defaults['category'] ?? $request->get('category');
        $category = CategorieEquipement::where('code', $categoryCode)->firstOrFail();

        if (! $request->filled('code_inventaire')) {
            $lastEquipement = Equipement::orderBy('id', 'desc')->first();
            $nextId = $lastEquipement ? $lastEquipement->id + 1 : 1;
            $request->merge(['code_inventaire' => 'INV-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT)]);
        }

        // Build dynamic validation rules
        $champsConfig = ChampConfig::where('categorie_id', $category->id)->get();
        $rules = [
            'code_inventaire' => 'required|string|unique:parc_info_equipements,code_inventaire',
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'date_fin_garantie' => 'nullable|date',
            'valeur_achat' => 'nullable|numeric',
            'duree_vie_probable' => 'nullable|integer',
            'ref_bordereau' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'statut' => 'required|in:en_stock_magasin,en_stock_dsi,en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'type_cible' => 'nullable|in:EMPLOYE,POSTE,LOCAL,DIRECTION,SERVICE,UNITE',
            'skip_affectation' => 'nullable|boolean',
        ];

        foreach ($champsConfig as $champ) {
            if ($champ->regles_validation) {
                $rules["champs_valeurs.{$champ->code}"] = $champ->regles_validation;
            }
        }

        $request->validate($rules);

        $equipementId = DB::transaction(function () use ($request, $category) {
            // Check status: if en_stock_magasin or en_stock_dsi, fallback to en_stock if any issue, but we support them
            $equipement = Equipement::create([
                'categorie_id' => $category->id,
                'code_inventaire' => $request->code_inventaire,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie' => $request->date_fin_garantie,
                'valeur_achat' => $request->valeur_achat,
                'duree_vie_probable' => $request->duree_vie_probable,
                'ref_bordereau' => $request->ref_bordereau,
                'statut' => $request->statut,
                'etat' => $request->etat ?? 'bon',
                'tags' => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
                'champs_valeurs' => $request->input('champs_valeurs', []),
            ]);

            // Save localization local_id if present
            if ($request->filled('local_id')) {
                $equipement->update(['local_id' => $request->local_id]);
            }

            // Create assignment if skip is false
            if (! $request->boolean('skip_affectation') && $request->filled('type_cible')) {
                $niveau_rattachement = null;
                $direction_id = null;
                $service_id = null;
                $unite_id = null;
                $cibleNom = 'Inconnu';

                if ($request->type_cible === 'EMPLOYE' && $request->dossier_employe_id) {
                    $employe = Employe::find($request->dossier_employe_id);
                    if ($employe) {
                        $niveau_rattachement = $employe->niveau_rattachement;
                        $direction_id = $employe->direction_id;
                        $service_id = $employe->service_id;
                        $unite_id = $employe->unite_id;
                        $cibleNom = $employe->full_name;
                    }
                } elseif ($request->type_cible === 'POSTE' && $request->poste_travail_id) {
                    $poste = PosteTravail::find($request->poste_travail_id);
                    if ($poste) {
                        $niveau_rattachement = $poste->niveau_rattachement;
                        $direction_id = $poste->direction_id;
                        $service_id = $poste->service_id;
                        $unite_id = $poste->unite_id;
                        $cibleNom = $poste->libelle.' ('.$poste->code.')';
                    }
                } elseif ($request->type_cible === 'DIRECTION') {
                    $direction_id = $request->direction_id ?: $request->direction_id_aff;
                    $direction = \Modules\Organisation\Models\Direction::find($direction_id);
                    if ($direction) {
                        $cibleNom = $direction->libelle;
                    }
                    $niveau_rattachement = 'DIRECTION';
                } elseif ($request->type_cible === 'SERVICE') {
                    $service_id = $request->service_id ?: $request->service_id_aff;
                    $direction_id = $request->direction_id ?: $request->direction_id_aff;
                    $service = \Modules\Organisation\Models\Service::find($service_id);
                    if ($service) {
                        $cibleNom = $service->libelle;
                    }
                    $niveau_rattachement = 'SERVICE';
                } elseif ($request->type_cible === 'UNITE') {
                    $unite_id = $request->unite_id ?: $request->unite_id_aff;
                    $service_id = $request->service_id ?: $request->service_id_aff;
                    $direction_id = $request->direction_id ?: $request->direction_id_aff;
                    $unite = \Modules\Organisation\Models\Unite::find($unite_id);
                    if ($unite) {
                        $cibleNom = $unite->libelle;
                    }
                    $niveau_rattachement = 'UNITE';
                }

                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => $request->type_cible,
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'date_fin' => $request->date_fin,
                    'dossier_employe_id' => $request->dossier_employe_id,
                    'poste_travail_id' => $request->poste_travail_id,
                    'local_id' => $request->local_id,
                    'niveau_rattachement' => $niveau_rattachement,
                    'direction_id' => $direction_id,
                    'service_id' => $service_id,
                    'unite_id' => $unite_id,
                ]);

                \Modules\ParcInfo\Models\HistoriqueChangement::create([
                    'equipement_id' => $equipement->id,
                    'date_changement' => now(),
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'AFFECTATION',
                    'ancien_statut' => null,
                    'nouveau_statut' => $equipement->statut,
                    'motif' => 'Affectation initiale : '.$request->type_cible.' - '.$cibleNom,
                ]);

                if ($request->local_id) {
                    $local = \Modules\Organisation\Models\Local::find($request->local_id);
                    $localNom = $local ? $local->nom_complet : 'Inconnu';

                    \Modules\ParcInfo\Models\HistoriqueChangement::create([
                        'equipement_id' => $equipement->id,
                        'date_changement' => now(),
                        'utilisateur_id' => auth()->id(),
                        'type_changement' => 'MOUVEMENT',
                        'motif' => "Localisation initiale : {$localNom}",
                    ]);
                }
            }

            return $equipement->id;
        });

        return response()->json([
            'success' => true,
            'message' => $category->libelle.' enregistré avec succès.',
            'equipement_id' => $equipementId,
        ]);
    }

    /**
     * Display details of a specific equipment.
     */
    public function show($id)
    {
        $equipement = Equipement::with([
            'categorie',
            'marque',
            'local.etage.batiment.site',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
            'affectations.employe',
            'affectations.posteTravail',
            'affectations.local',
            'affectations.direction', 'affectations.service', 'affectations.unite',
            'affectations.ligneBon.bon',
            'lignesBon.bon',
            'historique',
            'affectationsLicences.licence.logiciel',
        ])->findOrFail($id);

        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        // Fetch available licenses for software assignments
        $licencesDisponibles = Licence::with('logiciel')
            ->where('actif', true)
            ->get()
            ->filter(fn ($l) => $l->nombre_postes_utilises < $l->nombre_postes_accordes || $l->nombre_postes_accordes == 0);

        // Group dynamic fields by panel name
        $champs = ChampConfig::where('categorie_id', $equipement->categorie_id)
            ->where('afficher_dans_show', true)
            ->orderBy('ordre_affichage')
            ->get();

        $panels = [];
        foreach ($champs as $champ) {
            $panels[$champ->nom_panel][] = $champ;
        }

        return view('parcinfo::informatique.show', compact(
            'equipement', 'sites', 'marques', 'directions', 'licencesDisponibles', 'panels'
        ));
    }

    /**
     * Return json details of specific equipment.
     */
    public function showJson($id)
    {
        $e = Equipement::with([
            'categorie',
            'marque',
            'local.etage.batiment.site',
            'affectationActive.employe',
            'affectationActive.posteTravail.local.etage.batiment.site',
            'affectationActive.local.etage.batiment.site',
            'affectationActive.direction', 'affectationActive.service', 'affectationActive.unite',
        ])->findOrFail($id);

        return response()->json(['success' => true, 'data' => $e]);
    }

    /**
     * Update the dynamic technical sheet.
     */
    public function update(Request $request, $id)
    {
        $equipement = Equipement::findOrFail($id);

        // Build dynamic validation rules
        $champsConfig = ChampConfig::where('categorie_id', $equipement->categorie_id)->get();
        $rules = [
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
            'date_acquisition' => 'nullable|date',
            'date_mise_en_service' => 'nullable|date',
            'date_fin_garantie' => 'nullable|date',
            'valeur_achat' => 'nullable|numeric',
            'duree_vie_probable' => 'nullable|integer',
            'ref_bordereau' => 'nullable|string|max:255',
            'tags' => 'nullable|string',
            'statut' => 'required|in:en_stock_magasin,en_stock_dsi,en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
            'local_id' => 'nullable|exists:organisation_locaux,id',
        ];

        foreach ($champsConfig as $champ) {
            if ($champ->regles_validation) {
                $rules["champs_valeurs.{$champ->code}"] = $champ->regles_validation;
            }
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $equipement) {
            $ancienLocalId = $equipement->local_id;

            $equipement->update([
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'date_mise_en_service' => $request->date_mise_en_service,
                'date_fin_garantie' => $request->date_fin_garantie,
                'valeur_achat' => $request->valeur_achat,
                'duree_vie_probable' => $request->duree_vie_probable,
                'ref_bordereau' => $request->ref_bordereau,
                'statut' => $request->statut,
                'etat' => $request->etat,
                'local_id' => $request->local_id,
                'tags' => $request->tags ? array_map('trim', explode(',', $request->tags)) : null,
                'champs_valeurs' => $request->input('champs_valeurs', []),
            ]);

            if ($ancienLocalId != $request->local_id) {
                $ancienLocal = \Modules\Organisation\Models\Local::find($ancienLocalId);
                $nouveauLocal = \Modules\Organisation\Models\Local::find($request->local_id);

                $descAncien = $ancienLocal ? $ancienLocal->nom_complet : 'Aucun';
                $descNouveau = $nouveauLocal ? $nouveauLocal->nom_complet : 'Aucun';

                \Modules\ParcInfo\Models\HistoriqueChangement::create([
                    'equipement_id' => $equipement->id,
                    'date_changement' => now(),
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'MOUVEMENT',
                    'motif' => "Changement d'emplacement physique : {$descAncien} ➔ {$descNouveau}",
                ]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Fiche technique mise à jour avec succès.',
        ]);
    }

    /**
     * Update equipment status with historical tracking.
     */
    public function updateStatut(Request $request, $id)
    {
        $request->validate([
            'statut' => 'required|in:en_stock_magasin,en_stock_dsi,en_stock,en_service,en_reparation,perdu,reforme',
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $ancienStatut = $equipement->statut;

            // If switching back to stock, close active assignment
            if (str_starts_with($request->statut, 'en_stock') && $equipement->affectationActive) {
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
     * Update equipment physical condition with historical tracking.
     */
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

    /**
     * Create a new assignment.
     */
    public function storeAffectation(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'type_cible' => 'required|in:EMPLOYE,POSTE,LOCAL,DIRECTION,SERVICE,UNITE',
        ]);

        DB::transaction(function () use ($request) {
            $equipement = Equipement::findOrFail($request->equipement_id);

            // Close active assignment
            AffectationEquipement::where('equipement_id', $request->equipement_id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $niveau_rattachement = null;
            $direction_id = null;
            $service_id = null;
            $unite_id = null;
            $cibleNom = 'Inconnu';

            if ($request->type_cible === 'EMPLOYE' && $request->dossier_employe_id) {
                $employe = Employe::find($request->dossier_employe_id);
                if ($employe) {
                    $niveau_rattachement = $employe->niveau_rattachement;
                    $direction_id = $employe->direction_id;
                    $service_id = $employe->service_id;
                    $unite_id = $employe->unite_id;
                    $cibleNom = $employe->full_name;
                }
            } elseif ($request->type_cible === 'POSTE' && $request->poste_travail_id) {
                $poste = PosteTravail::find($request->poste_travail_id);
                if ($poste) {
                    $niveau_rattachement = $poste->niveau_rattachement;
                    $direction_id = $poste->direction_id;
                    $service_id = $poste->service_id;
                    $unite_id = $poste->unite_id;
                    $cibleNom = $poste->libelle.' ('.$poste->code.')';
                }
            } elseif ($request->type_cible === 'DIRECTION') {
                $direction_id = $request->direction_id ?: $request->direction_id_aff;
                $direction = \Modules\Organisation\Models\Direction::find($direction_id);
                if ($direction) {
                    $cibleNom = $direction->libelle;
                }
                $niveau_rattachement = 'DIRECTION';
            } elseif ($request->type_cible === 'SERVICE') {
                $service_id = $request->service_id ?: $request->service_id_aff;
                $direction_id = $request->direction_id ?: $request->direction_id_aff;
                $service = \Modules\Organisation\Models\Service::find($service_id);
                if ($service) {
                    $cibleNom = $service->libelle;
                }
                $niveau_rattachement = 'SERVICE';
            } elseif ($request->type_cible === 'UNITE') {
                $unite_id = $request->unite_id ?: $request->unite_id_aff;
                $service_id = $request->service_id ?: $request->service_id_aff;
                $direction_id = $request->direction_id ?: $request->direction_id_aff;
                $unite = \Modules\Organisation\Models\Unite::find($unite_id);
                if ($unite) {
                    $cibleNom = $unite->libelle;
                }
                $niveau_rattachement = 'UNITE';
            }

            AffectationEquipement::create([
                'code' => 'AFF-'.strtoupper(uniqid()),
                'equipement_id' => $request->equipement_id,
                'statut' => true,
                'type_cible' => $request->type_cible,
                'type_affectation' => 'PERMANENTE',
                'date_debut' => now(),
                'date_fin' => null,
                'dossier_employe_id' => $request->dossier_employe_id,
                'poste_travail_id' => $request->poste_travail_id,
                'local_id' => $request->local_id,
                'niveau_rattachement' => $niveau_rattachement,
                'direction_id' => $direction_id,
                'service_id' => $service_id,
                'unite_id' => $unite_id,
            ]);

            $ancienStatut = $equipement->statut;
            if (str_starts_with($equipement->statut, 'en_stock')) {
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
                'motif' => 'Nouvelle affectation : '.$request->type_cible.' - '.$cibleNom,
            ]);

            if ($request->local_id) {
                $local = \Modules\Organisation\Models\Local::find($request->local_id);
                $localNom = $local ? $local->nom_complet : 'Inconnu';

                HistoriqueChangement::create([
                    'equipement_id' => $request->equipement_id,
                    'date_changement' => now(),
                    'utilisateur_id' => auth()->id(),
                    'type_changement' => 'MOUVEMENT',
                    'motif' => "Mise en place physique : {$localNom}",
                ]);
            }
        });

        return response()->json(['success' => true, 'message' => 'Affectation enregistrée avec succès.']);
    }

    /**
     * Close the active assignment.
     */
    public function desaffecter(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'motif' => 'required|string',
        ]);

        DB::transaction(function () use ($request, $id) {
            $equipement = Equipement::findOrFail($id);
            $ancienStatut = $equipement->statut;
            $ancienLocal = $equipement->local;
            $descAncien = $ancienLocal ? $ancienLocal->nom_complet : 'Aucun';

            AffectationEquipement::where('equipement_id', $id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            $equipement->update(['statut' => 'en_stock', 'local_id' => null]);

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

            HistoriqueChangement::create([
                'equipement_id' => $id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'MOUVEMENT',
                'motif' => "Retrait de l'emplacement physique (Retour en stock) : {$descAncien}",
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Équipement désaffecté et mis en stock.']);
    }

    /**
     * Delete an equipment.
     */
    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();

        return response()->json(['success' => true, 'message' => 'Équipement supprimé avec succès.']);
    }

    /**
     * Quick add brand.
     */
    public function storeMarque(Request $request)
    {
        $request->validate(['libelle' => 'required|string|unique:parc_info_marques,libelle']);
        $marque = Marque::create(['libelle' => $request->libelle]);

        return response()->json(['success' => true, 'data' => $marque]);
    }

    /**
     * Quick add dictionary value.
     */
    public function storeDictionnaireValeur(Request $request)
    {
        $dictCode = $request->route()->defaults['dictionnaire_code'] ?? $request->get('dictionnaire_code');
        $request->validate(['libelle' => 'required|string']);

        $dict = Dictionnaire::where('code', $dictCode)->firstOrFail();

        // Prevent duplicate value for the same dictionary
        $valeur = DictionnaireValeur::firstOrCreate([
            'dictionnaire_id' => $dict->id,
            'valeur' => $request->libelle,
        ]);

        return response()->json(['success' => true, 'data' => $valeur]);
    }

    // ── AJAX searches ──────────────────────────────────────────────────────────

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

    public function imprimerEtiquette(int $id): \Illuminate\Contracts\View\View
    {
        $equipement = Equipement::with(['categorie', 'marque'])->findOrFail($id);

        return view('parcinfo::informatique.equipements.etiquette', compact('equipement'));
    }

    public function centreImpression(Request $request): \Illuminate\Contracts\View\View
    {
        $categories = CategorieEquipement::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.equipements.centre_impression', compact('categories', 'sites', 'directions'));
    }

    public function getEquipementsData(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = Equipement::query()
            ->with([
                'categorie',
                'marque',
                'affectationActive.employe',
                'affectationActive.posteTravail',
                'affectationActive.local',
                'affectationActive.direction',
                'affectationActive.service',
                'affectationActive.unite',
            ]);

        // Filter by category
        if ($request->filled('categorie_id')) {
            $query->where('categorie_id', $request->categorie_id);
        }

        // Apply general filters
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        if ($request->filled('site_id')) {
            $siteId = $request->site_id;
            $query->where(function ($q) use ($siteId) {
                $q->whereHas('affectationActive.posteTravail.local.etage.batiment', fn ($q2) => $q2->where('site_id', $siteId))
                    ->orWhereHas('affectationActive.local.etage.batiment', fn ($q2) => $q2->where('site_id', $siteId));
            });
        }

        if ($request->filled('direction_id')) {
            $query->whereHas('affectationActive', fn ($q) => $q->where('direction_id', $request->direction_id));
        }

        $likeOperator = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('code_inventaire', $likeOperator, "%{$s}%")
                ->orWhere('numero_serie', $likeOperator, "%{$s}%")
                ->orWhere('modele', $likeOperator, "%{$s}%")
                ->orWhereHas('marque', fn ($q2) => $q2->where('libelle', $likeOperator, "%{$s}%"))
            );
        }

        $sortField = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'desc');

        if ($sortField === 'categorie_libelle') {
            $query->join('parc_info_categories_equipements', 'parc_info_equipements.categorie_id', '=', 'parc_info_categories_equipements.id')
                ->select('parc_info_equipements.*')
                ->orderBy('parc_info_categories_equipements.libelle', $sortOrder);
        } elseif ($sortField === 'marque_modele') {
            $query->leftJoin('parc_info_marques', 'parc_info_equipements.marque_id', '=', 'parc_info_marques.id')
                ->select('parc_info_equipements.*')
                ->orderBy('parc_info_marques.libelle', $sortOrder)
                ->orderBy('parc_info_equipements.modele', $sortOrder);
        } else {
            $query->orderBy($sortField, $sortOrder);
        }

        $total = $query->count();
        $rows = $query->offset($request->get('offset', 0))->limit($request->get('limit', 25))->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(function ($e) {
                $aff = $e->affectationActive;
                $affLabel = '—';
                if ($aff) {
                    $affLabel = match ($aff->type_cible) {
                        'EMPLOYE' => $aff->employe?->full_name ?? '—',
                        'POSTE' => $aff->posteTravail?->code ?? '—',
                        'LOCAL' => $aff->local?->libelle ?? '—',
                        'DIRECTION' => $aff->direction?->libelle ?? '—',
                        'SERVICE' => $aff->service?->libelle ?? '—',
                        'UNITE' => $aff->unite?->libelle ?? '—',
                        default => '—',
                    };
                }

                return [
                    'id' => $e->id,
                    'code_inventaire' => $e->code_inventaire,
                    'categorie_libelle' => $e->categorie?->libelle ?? '—',
                    'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
                    'numero_serie' => $e->numero_serie ?? '—',
                    'statut' => $e->statut,
                    'statut_label' => $e->statut_label,
                    'affectation' => $affLabel,
                    'etat' => $e->etat,
                ];
            }),
        ]);
    }

    public function imprimerEtiquettesSelectionnees(Request $request): \Illuminate\Contracts\View\View
    {
        $idsStr = $request->get('ids', '');
        $ids = array_filter(explode(',', $idsStr));

        if (empty($ids)) {
            abort(400, 'Aucun équipement sélectionné.');
        }

        $equipements = Equipement::with(['categorie', 'marque'])->whereIn('id', $ids)->get();

        return view('parcinfo::informatique.equipements.etiquettes_multiples', compact('equipements'));
    }

    // ── Helper formatRow ────────────────────────────────────────────────────────

    private function formatRow(Equipement $e, $champsListe): array
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = match ($aff->type_cible) {
                'EMPLOYE' => $aff->employe?->full_name ?? '—',
                'POSTE' => $aff->posteTravail?->code ?? '—',
                'LOCAL' => $aff->local?->libelle ?? '—',
                'DIRECTION' => $aff->direction?->libelle ?? '—',
                'SERVICE' => $aff->service?->libelle ?? '—',
                'UNITE' => $aff->unite?->libelle ?? '—',
                default => '—',
            };
        }

        $champsValeursResolus = [];
        foreach ($champsListe as $champ) {
            $champsValeursResolus[$champ->code] = $e->getValeurAffichee($champ->code) ?? '—';
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? '—').' '.$e->modele,
            'champs_valeurs_resolus' => $champsValeursResolus,
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
            'etat' => $e->etat,
        ];
    }
}
