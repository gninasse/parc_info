<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Scanner;
use Modules\ParcInfo\Models\Marque;

class ScannerController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.scanners.index', compact(
            'sites', 'directions', 'marques'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'scanner', 'affectationActive.local.etage.batiment'])
            ->whereHas('scanner');

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('site_id')) {
            $query->whereHas('affectationActive.local.etage.batiment', fn ($q) => $q->where('site_id', $request->site_id));
        }

        return response()->json([
            'total' => $query->count(),
            'rows' => $query->get()->map(fn ($e) => $this->formatRow($e)),
        ]);
    }

    private function formatRow(Equipement $e)
    {
        $aff = $e->affectationActive;
        $affLabel = '—';
        if ($aff) {
            $affLabel = $aff->local ? $aff->local->libelle : 'Non localisé';
        }

        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? 'N/A').' '.$e->modele,
            'resolution' => $e->scanner->resolution_dpi_max ? $e->scanner->resolution_dpi_max.' DPI' : '—',
            'format_max' => $e->scanner->format_max ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $affLabel,
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'modele' => 'required|string|max:255',
            'statut' => 'required|in:en_stock,en_service,en_reparation,perdu,reforme',
            'etat' => 'required|in:bon,passable,mauvais,avarie',
        ]);

        $equipementId = DB::transaction(function () use ($request) {
            $code = $request->code_inventaire;
            if (!$code) {
                $last = Equipement::orderBy('id', 'desc')->first();
                $nextId = $last ? $last->id + 1 : 1;
                $code = 'SCN-'.date('Y').'-'.str_pad($nextId, 4, '0', STR_PAD_LEFT);
            }

            $equipement = Equipement::create([
                'code_inventaire' => $code,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
                'statut' => $request->statut,
                'etat' => $request->etat ?? 'bon',
            ]);

            Scanner::create([
                'equipement_id' => $equipement->id,
                'resolution_dpi_max' => $request->resolution_dpi_max,
                'format_max' => $request->format_max,
                'est_recto_verso' => $request->boolean('est_recto_verso'),
                'a_chargeur_auto' => $request->boolean('a_chargeur_auto'),
            ]);

            if (!$request->boolean('skip_affectation') && $request->filled('local_id')) {
                AffectationEquipement::create([
                    'code' => 'AFF-'.strtoupper(uniqid()),
                    'equipement_id' => $equipement->id,
                    'statut' => true,
                    'type_cible' => 'LOCAL',
                    'type_affectation' => 'PERMANENTE',
                    'date_debut' => now()->format('Y-m-d'),
                    'local_id' => $request->local_id,
                ]);
            }

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Scanner enregistré.', 'id' => $equipementId]);
    }

    public function show($id)
    {
        $equipement = Equipement::with([
            'marque',
            'scanner',
            'affectationActive.local.etage.batiment.site',
            'affectations.local',
            'historique',
        ])->findOrFail($id);

        if (request()->wantsJson()) {
            return response()->json([
                'id' => $equipement->id,
                'code_inventaire' => $equipement->code_inventaire,
                'numero_serie' => $equipement->numero_serie,
                'marque_id' => $equipement->marque_id,
                'modele' => $equipement->modele,
                'statut' => $equipement->statut,
                'etat' => $equipement->etat,
                'date_acquisition' => $equipement->date_acquisition?->format('Y-m-d'),
                'resolution_dpi_max' => $equipement->scanner->resolution_dpi_max,
                'format_max' => $equipement->scanner->format_max,
                'est_recto_verso' => $equipement->scanner->est_recto_verso,
                'a_chargeur_auto' => $equipement->scanner->a_chargeur_auto,
            ]);
        }

        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.scanners.show', compact(
            'equipement', 'marques', 'sites', 'directions'
        ));
    }

    public function update(Request $request, $id)
    {
        $equipement = Equipement::findOrFail($id);
        $request->validate([
            'numero_serie' => "required|string|unique:parc_info_equipements,numero_serie,{$id}",
            'modele' => 'required|string|max:255',
        ]);

        DB::transaction(function () use ($request, $equipement) {
            $equipement->update([
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'date_acquisition' => $request->date_acquisition,
            ]);

            Scanner::where('equipement_id', $equipement->id)->update([
                'resolution_dpi_max' => $request->resolution_dpi_max,
                'format_max' => $request->format_max,
                'est_recto_verso' => $request->boolean('est_recto_verso'),
                'a_chargeur_auto' => $request->boolean('a_chargeur_auto'),
            ]);
        });

        return response()->json(['success' => true, 'message' => 'Scanner mis à jour.']);
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();
        return response()->json(['success' => true, 'message' => 'Équipement supprimé.']);
    }

    public function updateStatut(Request $request, $id)
    {
        $e = Equipement::findOrFail($id);
        $old = $e->statut;
        $e->update(['statut' => $request->statut]);

        HistoriqueChangement::create([
            'equipement_id' => $e->id,
            'utilisateur_id' => auth()->id(),
            'type_changement' => 'STATUT',
            'ancien_statut' => $old,
            'nouveau_statut' => $request->statut,
            'motif' => $request->motif ?? 'Changement de statut manuel',
        ]);

        return response()->json(['success' => true, 'message' => 'Statut mis à jour.']);
    }

    public function desaffecter(Request $request, $id)
    {
        $e = Equipement::findOrFail($id);
        $aff = $e->affectationActive;
        if ($aff) {
            $aff->update(['date_fin' => now(), 'statut' => false]);
            $e->update(['statut' => 'en_stock']);
            
            HistoriqueChangement::create([
                'equipement_id' => $e->id,
                'utilisateur_id' => auth()->id(),
                'type_changement' => 'AFFECTATION',
                'motif' => $request->motif ?? 'Désaffectation manuelle',
            ]);
        }
        return response()->json(['success' => true, 'message' => 'Équipement remis en stock.']);
    }
}
