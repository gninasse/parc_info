<?php

namespace Modules\ParcInfo\Http\Controllers\Analyse;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Modules\ParcInfo\Models\Equipement;

class EtatController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:parc-info.analyse.etats.view', only: ['index', 'getData']),
        ];
    }

    /**
     * Display the listing view.
     */
    public function index()
    {
        return view('parcinfo::analyse.etats.index');
    }

    /**
     * Get data for the Bootstrap Table.
     */
    public function getData(Request $request)
    {
        $query = Equipement::query()->with(['marque']);

        if ($request->has('statut') && ! empty($request->statut)) {
            $query->where('statut', $request->statut);
        }

        if ($request->has('etat') && ! empty($request->etat)) {
            $query->where('etat', $request->etat);
        }

        if ($request->has('search') && ! empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code_inventaire', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('modele', 'like', "%{$search}%");
            });
        }

        $sortBy = $request->get('sort', 'id');
        $sortOrder = $request->get('order', 'asc');

        // Handle sorting by brand relationship if needed
        if ($sortBy === 'marque') {
            $query->join('parc_info_marques', 'parc_info_equipements.marque_id', '=', 'parc_info_marques.id')
                ->orderBy('parc_info_marques.libelle', $sortOrder)
                ->select('parc_info_equipements.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $limit = $request->get('limit', 10);
        $offset = $request->get('offset', 0);

        $total = $query->count();
        $rows = $query->offset($offset)->limit($limit)->get();

        // Map status/etat labels in French
        $rows->transform(function ($eq) {
            $statutLabels = [
                'en_stock' => 'En stock',
                'en_service' => 'En service',
                'en_reparation' => 'En réparation',
                'perdu' => 'Perdu',
                'reforme' => 'Réformé',
            ];

            $etatLabels = [
                'bon' => 'Bon',
                'passable' => 'Passable',
                'mauvais' => 'Mauvais',
                'avarie' => 'Avarié',
            ];

            $eq->statut_label = $statutLabels[$eq->statut] ?? $eq->statut;
            $eq->etat_label = $etatLabels[$eq->etat] ?? $eq->etat;
            $eq->marque_libelle = $eq->marque ? $eq->marque->libelle : '-';

            return $eq;
        });

        return response()->json([
            'total' => $total,
            'rows' => $rows,
        ]);
    }
}
