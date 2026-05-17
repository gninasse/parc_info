<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ParcInfo\Models\Equipement;

class ParcInfoController extends Controller
{
    public function dashboard(): \Illuminate\View\View
    {
        $stats = [
            'total_equipements' => 342,
            'en_service' => 278,
            'en_maintenance' => 31,
            'hors_service' => 33,
            'postes_travail' => 195,
            'serveurs' => 18,
            'imprimantes' => 47,
            'equipements_reseau' => 82,
            'garantie_expiree' => 56,
            'renouvellement_prevu' => 24,
        ];

        $recentEquipements = [
            ['code' => 'PC-DRH-042', 'libelle' => 'PC Bureau HP EliteDesk', 'type' => 'Poste de travail', 'site' => 'Siège Ouagadougou', 'statut' => 'En service',    'statut_color' => 'success', 'date' => 'Il y a 2 jours'],
            ['code' => 'IMP-LAB-011', 'libelle' => 'Imprimante HP LaserJet',  'type' => 'Imprimante',      'site' => 'Laboratoire Central', 'statut' => 'Maintenance',  'statut_color' => 'warning', 'date' => 'Il y a 5 jours'],
            ['code' => 'SRV-DC-003',  'libelle' => 'Serveur Dell PowerEdge',  'type' => 'Serveur',         'site' => 'Datacenter',          'statut' => 'En service',    'statut_color' => 'success', 'date' => 'Il y a 1 semaine'],
            ['code' => 'SW-RES-017',  'libelle' => 'Switch Cisco Catalyst',   'type' => 'Réseau',          'site' => 'Bâtiment A',          'statut' => 'En service',    'statut_color' => 'success', 'date' => 'Il y a 1 semaine'],
            ['code' => 'PC-URG-088',  'libelle' => 'Laptop Lenovo ThinkPad',  'type' => 'Poste de travail', 'site' => 'Urgences',           'statut' => 'Hors service',  'statut_color' => 'danger',  'date' => 'Il y a 2 semaines'],
        ];

        $repartitionParType = [
            ['label' => 'Postes de travail', 'count' => 195, 'percent' => 57, 'color' => 'primary',   'icon' => 'bi bi-pc-display'],
            ['label' => 'Imprimantes',        'count' => 47,  'percent' => 14, 'color' => 'success',   'icon' => 'bi bi-printer'],
            ['label' => 'Équip. réseau',      'count' => 82,  'percent' => 24, 'color' => 'info',      'icon' => 'bi bi-router'],
            ['label' => 'Serveurs',           'count' => 18,  'percent' => 5,  'color' => 'warning',   'icon' => 'bi bi-server'],
        ];

        return view('parcinfo::dashboard.index', compact('stats', 'recentEquipements', 'repartitionParType'));
    }

    public function searchEquipements(Request $request)
    {
        $q = $request->get('q', '');

        $query = Equipement::with(['marque', 'affectationActive.local.etage.batiment'])
            ->where(function ($query) use ($q) {
                $query->where('code_inventaire', 'ilike', "%{$q}%")
                    ->orWhere('modele', 'ilike', "%{$q}%")
                    ->orWhere('numero_serie', 'ilike', "%{$q}%")
                    ->orWhereHas('marque', fn ($m) => $m->where('libelle', 'ilike', "%{$q}%"));
            });

        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        return response()->json($query->limit(50)->get()->map(function ($e) {
            return [
                'id' => $e->id,
                'code' => $e->code_inventaire,
                'modele' => $e->modele,
                'marque' => $e->marque?->libelle ?: 'Générique',
                'statut' => $e->statut,
                'statut_label' => $e->statut_label,
                'emplacement' => $e->affectationActive?->local?->libelle ?? '—',
            ];
        }));
    }
}
