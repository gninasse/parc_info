<?php

namespace Modules\Organisation\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PosteTravailRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'libelle' => 'required|max:255',
            'description' => 'nullable|string',
            'niveau_rattachement' => 'required|in:direction,service,unite',
            'direction_id' => 'required|exists:organisation_directions,id',
            'service_id' => [
                'nullable',
                'exists:organisation_services,id',
                'required_if:niveau_rattachement,service',
                'required_if:niveau_rattachement,unite'
            ],
            'unite_id' => 'nullable|exists:organisation_unites,id|required_if:niveau_rattachement,unite',
            'site_id' => 'nullable|exists:organisation_sites,id',
            'batiment_id' => 'nullable|exists:organisation_batiments,id',
            'etage_id' => 'nullable|exists:organisation_etages,id',
            'local_id' => 'nullable|exists:organisation_locaux,id',
            'dossier_employe_id' => 'nullable|exists:grh_dossiers_employes,id',
            'statut' => 'required|in:actif,inactif,en_renovation,supprime',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'service_id.required_if' => 'Le service est requis pour ce niveau de rattachement.',
            'unite_id.required_if' => 'L\'unité est requise pour ce niveau de rattachement.',
        ];
    }
}
