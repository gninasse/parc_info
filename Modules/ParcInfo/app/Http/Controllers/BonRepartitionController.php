<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Service;
use Modules\ParcInfo\Http\Requests\AjouterLigneBonRequest;
use Modules\ParcInfo\Http\Requests\StoreBonRepartitionRequest;
use Modules\ParcInfo\Http\Requests\UpdateLigneBonRequest;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\BonRepartition;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\LigneBonRepartition;

class BonRepartitionController extends Controller
{
    /**
     * Display the list of bons de répartition.
     */
    public function index(): \Illuminate\View\View
    {
        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get(['id', 'nom']);

        return view('parcinfo::informatique.bons-repartition.index', compact('fournisseurs'));
    }

    /**
     * Fetch paginated data for Bootstrap Table.
     */
    public function data(Request $request): JsonResponse
    {
        $query = BonRepartition::query()
            ->with(['fournisseur', 'lignes']);

        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        if ($request->filled('statut')) {
            if ($request->statut === 'cloture') {
                $query->whereDoesntHave('lignes', fn ($q) => $q->where('est_signe', false));
            } elseif ($request->statut === 'en_cours') {
                $query->whereHas('lignes', fn ($q) => $q->where('est_signe', false));
            }
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_bon', '>=', $request->date_debut);
        }

        if ($request->filled('date_fin')) {
            $query->whereDate('date_bon', '<=', $request->date_fin);
        }

        if ($request->filled('search') && $request->search !== '') {
            $s = $request->search;
            $query->where(fn ($q) => $q
                ->where('numero_bon', 'ilike', "%{$s}%")
                ->orWhereHas('fournisseur', fn ($q2) => $q2->where('nom', 'ilike', "%{$s}%"))
            );
        }

        $total = $query->count();
        $rows = $query
            ->orderBy($request->get('sort', 'id'), $request->get('order', 'desc'))
            ->offset($request->get('offset', 0))
            ->limit($request->get('limit', 25))
            ->get();

        return response()->json([
            'total' => $total,
            'rows' => $rows->map(function (BonRepartition $bon) {
                $total = $bon->lignes->count();
                $signees = $bon->lignes->where('est_signe', true)->count();
                $statut = $bon->statut_label;

                return [
                    'id' => $bon->id,
                    'numero_bon' => $bon->numero_bon,
                    'date_bon' => $bon->date_bon?->format('d/m/Y'),
                    'fournisseur' => $bon->fournisseur?->nom ?? '—',
                    'nb_lignes' => $total,
                    'nb_signees' => $signees,
                    'progression' => $total > 0 ? round(($signees / $total) * 100) : 0,
                    'statut' => $statut['label'],
                    'statut_color' => $statut['color'],
                ];
            }),
        ]);
    }

    /**
     * Store a new bon de répartition.
     */
    public function store(StoreBonRepartitionRequest $request): JsonResponse
    {
        $bon = BonRepartition::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => "Bon {$bon->numero_bon} créé avec succès.",
            'bon_id' => $bon->id,
            'numero_bon' => $bon->numero_bon,
            'redirect' => route('parc-info.bons-repartition.show', $bon->id),
        ]);
    }

    /**
     * Display a bon de répartition with its lignes.
     */
    public function show(int $id): \Illuminate\View\View
    {
        $bon = BonRepartition::with([
            'fournisseur',
            'lignes.equipement.categorie',
            'lignes.equipement.marque',
            'lignes.direction',
            'lignes.service',
            'lignes.affectation',
        ])->findOrFail($id);

        $fournisseurs = Fournisseur::where('est_actif', true)->orderBy('nom')->get(['id', 'nom']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $services = Service::where('actif', true)->orderBy('libelle')->get(['id', 'libelle', 'direction_id']);

        return view('parcinfo::informatique.bons-repartition.show', compact(
            'bon', 'fournisseurs', 'directions', 'services'
        ));
    }

    /**
     * Update the header of a bon.
     */
    public function update(StoreBonRepartitionRequest $request, int $id): JsonResponse
    {
        $bon = BonRepartition::findOrFail($id);
        $bon->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Bon mis à jour avec succès.']);
    }

    /**
     * Delete a bon (only if no signed lignes).
     */
    public function destroy(int $id): JsonResponse
    {
        $bon = BonRepartition::with('lignes')->findOrFail($id);

        if ($bon->lignes->where('est_signe', true)->count() > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer un bon contenant des lignes déjà signées.',
            ], 422);
        }

        $bon->delete();

        return response()->json(['success' => true, 'message' => 'Bon supprimé avec succès.']);
    }

    /**
     * Add an equipment line to a bon.
     */
    public function addLigne(AjouterLigneBonRequest $request, int $bonId): JsonResponse
    {
        $bon = BonRepartition::findOrFail($bonId);

        $equipement = Equipement::findOrFail($request->equipement_id);

        // Check equipment is in stock
        $statutsStock = ['en_stock', 'en_stock_magasin', 'en_stock_dsi'];
        if (! in_array($equipement->statut, $statutsStock, true)) {
            return response()->json([
                'success' => false,
                'message' => "L'équipement {$equipement->code_inventaire} n'est pas en stock.",
            ], 422);
        }

        // Check not already in an active bon
        $dejaInBon = LigneBonRepartition::where('equipement_id', $equipement->id)
            ->where('est_signe', false)
            ->exists();

        if ($dejaInBon) {
            return response()->json([
                'success' => false,
                'message' => "L'équipement {$equipement->code_inventaire} est déjà dans un bon en attente.",
            ], 422);
        }

        $ligne = $bon->lignes()->create($request->validated());
        $ligne->load(['equipement.categorie', 'equipement.marque', 'direction', 'service']);

        return response()->json([
            'success' => true,
            'message' => "Équipement {$equipement->code_inventaire} ajouté au bon.",
            'ligne' => $this->formatLigne($ligne),
        ]);
    }

    /**
     * Update a ligne (destination, receptionist, delivery date).
     */
    public function updateLigne(UpdateLigneBonRequest $request, int $bonId, int $ligneId): JsonResponse
    {
        $ligne = LigneBonRepartition::where('bon_id', $bonId)->findOrFail($ligneId);

        if ($ligne->est_signe) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de modifier une ligne déjà signée.',
            ], 422);
        }

        $ligne->update($request->validated());

        return response()->json(['success' => true, 'message' => 'Ligne mise à jour.']);
    }

    /**
     * Remove an unsigned ligne from a bon.
     */
    public function removeLigne(int $bonId, int $ligneId): JsonResponse
    {
        $ligne = LigneBonRepartition::where('bon_id', $bonId)->findOrFail($ligneId);

        if ($ligne->est_signe) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une ligne déjà signée.',
            ], 422);
        }

        $ligne->delete();

        return response()->json(['success' => true, 'message' => 'Ligne supprimée.']);
    }

    /**
     * Sign a ligne: create the AffectationEquipement and update equipment status.
     * This mirrors the logic in EquipementDynamiqueController::storeAffectation().
     */
    public function signerLigne(int $bonId, int $ligneId): JsonResponse
    {
        DB::transaction(function () use ($bonId, $ligneId) {
            $ligne = LigneBonRepartition::with(['bon', 'equipement', 'direction', 'service'])
                ->where('bon_id', $bonId)
                ->findOrFail($ligneId);

            abort_if($ligne->est_signe, 422, 'Cette ligne est déjà signée.');

            $equipement = $ligne->equipement;
            $bon = $ligne->bon;

            // 1. Clôturer l'affectation active existante (same as storeAffectation)
            AffectationEquipement::where('equipement_id', $equipement->id)
                ->where('statut', true)
                ->update(['statut' => false, 'date_fin' => now()]);

            // 2. Résoudre direction_id / service_id selon type_cible
            $directionId = null;
            $serviceId = null;
            $niveauRattachement = $ligne->type_cible;
            $cibleNom = '—';

            if ($ligne->type_cible === 'DIRECTION') {
                $directionId = $ligne->direction_id;
                $cibleNom = $ligne->direction?->libelle ?? '—';
            } elseif ($ligne->type_cible === 'SERVICE') {
                $serviceId = $ligne->service_id;
                $directionId = $ligne->service?->direction_id;
                $cibleNom = $ligne->service?->libelle ?? '—';
            }

            // 3. Créer la nouvelle AffectationEquipement
            $affectation = AffectationEquipement::create([
                'code' => 'AFF-BON-'.strtoupper(uniqid()),
                'equipement_id' => $equipement->id,
                'statut' => true,
                'type_cible' => $ligne->type_cible,
                'type_affectation' => 'PERMANENTE',
                'date_debut' => $ligne->date_livraison ?? now()->toDateString(),
                'date_fin' => null,
                'direction_id' => $directionId,
                'service_id' => $serviceId,
                'niveau_rattachement' => $niveauRattachement,
            ]);

            // 4. Mettre à jour statut équipement → en_service
            $ancienStatut = $equipement->statut;
            $equipement->update(['statut' => 'en_service']);

            // 5. Enregistrer l'historique (STATUT + AFFECTATION)
            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'STATUT',
                'ancien_statut' => $ancienStatut,
                'nouveau_statut' => 'en_service',
                'motif' => "Mise en service via Bon {$bon->numero_bon}",
                'reference_document' => $bon->numero_bon,
            ]);

            HistoriqueChangement::create([
                'equipement_id' => $equipement->id,
                'date_changement' => now(),
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'AFFECTATION',
                'ancien_statut' => null,
                'nouveau_statut' => "en_service — {$ligne->type_cible} : {$cibleNom}",
                'motif' => "Affectation via Bon {$bon->numero_bon} — réceptionné par : ".($ligne->nom_receptionniste ?? 'N/A'),
                'reference_document' => $bon->numero_bon,
            ]);

            // 6. Marquer la ligne comme signée et lier l'affectation créée
            $ligne->update([
                'est_signe' => true,
                'date_signature' => now(),
                'affectation_id' => $affectation->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Réception confirmée. Affectation enregistrée.',
        ]);
    }

    /**
     * Print view for a bon as a PDF stream.
     */
    public function imprimer(int $id): \Illuminate\Http\Response
    {
        $bon = BonRepartition::with([
            'fournisseur',
            'lignes.equipement.categorie',
            'lignes.equipement.marque',
            'lignes.direction',
            'lignes.service',
        ])->findOrFail($id);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('parcinfo::informatique.bons-repartition.imprimer', compact('bon'));
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("bon_repartition_{$bon->numero_bon}.pdf");
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function formatLigne(LigneBonRepartition $ligne): array
    {
        return [
            'id' => $ligne->id,
            'equipement_id' => $ligne->equipement_id,
            'code_inventaire' => $ligne->equipement->code_inventaire,
            'numero_serie' => $ligne->equipement->numero_serie,
            'categorie' => $ligne->equipement->categorie?->libelle,
            'marque_modele' => trim(($ligne->equipement->marque?->libelle ?? '').' '.$ligne->equipement->modele),
            'type_cible' => $ligne->type_cible,
            'direction_id' => $ligne->direction_id,
            'direction_libelle' => $ligne->direction?->libelle,
            'service_id' => $ligne->service_id,
            'service_libelle' => $ligne->service?->libelle,
            'cible_label' => $ligne->cible_label,
            'nom_receptionniste' => $ligne->nom_receptionniste,
            'date_livraison' => $ligne->date_livraison?->format('Y-m-d'),
            'date_livraison_fmt' => $ligne->date_livraison?->format('d/m/Y'),
            'est_signe' => $ligne->est_signe,
            'date_signature' => $ligne->date_signature?->format('d/m/Y H:i'),
            'observation' => $ligne->observation,
        ];
    }
}
