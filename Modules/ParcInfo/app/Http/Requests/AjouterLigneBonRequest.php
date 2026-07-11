<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AjouterLigneBonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'equipement_id' => ['required', 'exists:parc_info_equipements,id'],
            'type_cible' => ['required', 'in:DIRECTION,SERVICE'],
            'direction_id' => ['nullable', 'exists:organisation_directions,id'],
            'service_id' => ['nullable', 'exists:organisation_services,id'],
            'nom_receptionniste' => ['nullable', 'string', 'max:255'],
            'date_livraison' => ['nullable', 'date'],
            'observation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'equipement_id.required' => "L'équipement est obligatoire.",
            'equipement_id.exists' => "L'équipement sélectionné n'existe pas.",
            'type_cible.required' => 'Veuillez choisir une direction ou un service.',
        ];
    }
}
