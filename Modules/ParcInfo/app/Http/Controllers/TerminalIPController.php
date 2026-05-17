<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\EquipementReseau;
use Modules\ParcInfo\Models\HistoriqueChangement;
use Modules\ParcInfo\Models\Marque;
use Modules\ParcInfo\Models\TypeReseau;

class TerminalIPController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $directions = Direction::where('actif', true)->orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.terminaux_ip.index', compact(
            'sites', 'directions', 'marques', 'typesReseaux'
        ));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'reseau.typeReseau', 'affectationActive.local.etage.batiment'])
            ->whereHas('reseau', function($q) {
                // On peut filtrer ici par des types spécifiques si on veut
            });

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('type_reseau_id')) {
            $query->whereHas('reseau', fn ($q) => $q->where('type_reseau_id', $request->type_reseau_id));
        }

        return response()->json([
            'total' => $query->count(),
            'rows' => $query->get()->map(fn ($e) => $this->formatRow($e)),
        ]);
    }

    private function formatRow(Equipement $e)
    {
        $aff = $e->affectationActive;
        return [
            'id' => $e->id,
            'code_inventaire' => $e->code_inventaire,
            'marque_modele' => ($e->marque?->libelle ?? 'N/A').' '.$e->modele,
            'type_reseau' => $e->reseau->typeReseau?->libelle ?? 'Terminal IP',
            'adresse_ip' => $e->reseau->adresse_ip ?? '—',
            'statut' => $e->statut,
            'statut_label' => $e->statut_label,
            'affectation' => $aff ? ($aff->local ? $aff->local->libelle : 'Assigné') : 'En stock',
        ];
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_serie' => 'required|string|unique:parc_info_equipements,numero_serie',
            'modele' => 'required|string|max:255',
            'adresse_ip' => 'nullable|ip',
        ]);

        $id = DB::transaction(function () use ($request) {
            $code = $request->code_inventaire ?: 'TIP-'.date('Y').'-'.rand(1000, 9999);
            $equipement = Equipement::create([
                'code_inventaire' => $code,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'statut' => $request->statut ?? 'en_stock',
                'etat' => $request->etat ?? 'bon',
            ]);

            EquipementReseau::create([
                'equipement_id' => $equipement->id,
                'type_reseau_id' => $request->type_reseau_id,
                'adresse_ip' => $request->adresse_ip,
            ]);

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Terminal IP enregistré.', 'id' => $id]);
    }

    public function show($id)
    {
        $equipement = Equipement::with(['marque', 'reseau.typeReseau', 'affectationActive.local'])->findOrFail($id);
        if (request()->wantsJson()) return response()->json($equipement);
        
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);
        $typesReseaux = TypeReseau::orderBy('libelle')->get(['id', 'libelle']);
        return view('parcinfo::informatique.terminaux_ip.show', compact('equipement', 'marques', 'typesReseaux'));
    }

    public function update(Request $request, $id)
    {
        $e = Equipement::findOrFail($id);
        $e->update($request->only(['numero_serie', 'modele', 'marque_id']));
        EquipementReseau::where('equipement_id', $id)->update($request->only(['adresse_ip', 'type_reseau_id']));
        return response()->json(['success' => true, 'message' => 'Mis à jour.']);
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
