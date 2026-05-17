<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('licence');

        return [
            'logiciel_id' => 'required|exists:parc_info_logiciels,id',
            'cle_licence' => 'nullable|string|max:255',
            'numero_contrat' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('parc_info_licences', 'numero_contrat')->ignore($id),
            ],
            'contrat_maintenance_id' => 'nullable|exists:parc_info_contrats_maintenances,id',
            'type_activation' => 'required|in:volume,concurrent,subscription,free',
            'modele_licencing' => 'required|in:device,user,concurrent,named',
            'nombre_postes_accordes' => 'required|integer|min:0',
            'date_acquisition' => 'required|date',
            'date_activation' => 'nullable|date',
            'date_expiration' => 'required|date|after_or_equal:date_acquisition',
            'date_renouvellement_prochain' => 'nullable|date|after:date_acquisition',
            'cout_unitaire' => 'nullable|numeric|min:0',
            'cout_total' => 'nullable|numeric|min:0',
            'devise' => 'required|string|size:3',
            'fournisseur_id' => 'required|exists:parc_info_fournisseurs,id',
            'contact_support_id' => 'nullable|exists:parc_info_contacts,id',
            'statut' => 'required|in:actif,expire,en_renouvellement,suspendu',
            'conditions_utilisation' => 'nullable|string|max:255',
            'actif' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }
}
