<?php

namespace Modules\ParcInfo\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\ParcInfo\Models\ContratMaintenance;
use Modules\ParcInfo\Models\Fournisseur;

class ContratMaintenanceController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reference' => 'required|string|unique:parc_info_contrats_maintenances,reference',
            'nom' => 'required|string',
            'fournisseur_id' => 'required|exists:parc_info_fournisseurs,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'cout' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $validated['est_actif'] = true;
        $contrat = ContratMaintenance::create($validated);

        activity('contrat')
            ->performedOn($contrat)
            ->causedBy(auth()->user())
            ->log('Création de contrat de maintenance');

        return response()->json(['success' => true, 'message' => 'Contrat créé avec succès.', 'data' => $contrat]);
    }

    public function show($id)
    {
        $contrat = ContratMaintenance::findOrFail($id);
        return response()->json($contrat);
    }

    public function update(Request $request, $id)
    {
        $contrat = ContratMaintenance::findOrFail($id);
        $validated = $request->validate([
            'reference' => 'required|string|unique:parc_info_contrats_maintenances,reference,' . $id,
            'nom' => 'required|string',
            'fournisseur_id' => 'required|exists:parc_info_fournisseurs,id',
            'date_debut' => 'nullable|date',
            'date_fin' => 'nullable|date|after_or_equal:date_debut',
            'cout' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $contrat->update($validated);

        activity('contrat')
            ->performedOn($contrat)
            ->causedBy(auth()->user())
            ->log('Mise à jour de contrat de maintenance');

        return response()->json(['success' => true, 'message' => 'Contrat mis à jour.']);
    }

    public function destroy($id)
    {
        $contrat = ContratMaintenance::findOrFail($id);
        if ($contrat->licences()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Impossible de supprimer un contrat lié à des licences.'], 422);
        }
        $contrat->delete();
        return response()->json(['success' => true, 'message' => 'Contrat supprimé.']);
    }
}
