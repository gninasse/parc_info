<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsommerConsommableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantite' => 'required|integer|min:1',
            'equipement_id' => 'nullable|exists:parc_info_equipements,id',
            'employe_id' => 'nullable|exists:grh_dossiers_employes,id',
            'service_id' => 'nullable|exists:organisation_services,id',
            'unite_id' => 'nullable|exists:organisation_unites,id',
            'raison' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
