<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Organisation\Models\Direction;
use Modules\Organisation\Models\Site;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\CameraIP;
use Modules\ParcInfo\Models\Marque;

class CameraController extends Controller
{
    public function index()
    {
        $sites = Site::orderBy('libelle')->get(['id', 'libelle']);
        $marques = Marque::orderBy('libelle')->get(['id', 'libelle']);

        return view('parcinfo::informatique.cameras.index', compact('sites', 'marques'));
    }

    public function getData(Request $request)
    {
        $query = Equipement::query()
            ->with(['marque', 'camera', 'affectationActive.local.etage.batiment'])
            ->whereHas('camera');

        return response()->json([
            'total' => $query->count(),
            'rows' => $query->get()->map(fn ($e) => [
                'id' => $e->id,
                'code_inventaire' => $e->code_inventaire,
                'marque_modele' => ($e->marque?->libelle ?? 'N/A').' '.$e->modele,
                'adresse_ip' => $e->camera->adresse_ip ?? '—',
                'statut' => $e->statut,
                'statut_label' => $e->statut_label,
                'affectation' => $e->affectationActive?->local?->libelle ?? 'Non localisé',
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $id = DB::transaction(function () use ($request) {
            $code = $request->code_inventaire ?: 'CAM-'.date('Y').'-'.rand(1000, 9999);
            $equipement = Equipement::create([
                'code_inventaire' => $code,
                'numero_serie' => $request->numero_serie,
                'marque_id' => $request->marque_id,
                'modele' => $request->modele,
                'statut' => $request->statut ?? 'en_service',
                'etat' => $request->etat ?? 'bon',
            ]);

            CameraIP::create([
                'equipement_id' => $equipement->id,
                'adresse_ip' => $request->adresse_ip,
                'type_camera' => $request->type_camera,
            ]);

            return $equipement->id;
        });

        return response()->json(['success' => true, 'message' => 'Caméra IP enregistrée.']);
    }

    public function show($id)
    {
        $equipement = Equipement::with(['marque', 'camera', 'affectationActive.local'])->findOrFail($id);
        return view('parcinfo::informatique.cameras.show', compact('equipement'));
    }

    public function destroy($id)
    {
        Equipement::findOrFail($id)->delete();
        return response()->json(['success' => true]);
    }
}
