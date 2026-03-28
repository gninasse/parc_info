<?php

namespace Modules\Grh\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'matricule' => 'required|string|unique:grh_dossiers_employes,matricule',
            'nom' => 'required|string|max:255',
            'prenom' => 'required|string|max:255',
            'date_naissance' => 'nullable|date',
            'genre' => 'nullable|in:M,F',
            'date_embauche' => 'nullable|date',
            'poste' => 'nullable|string|max:255',
            'niveau_rattachement' => 'required|in:direction,service,unite',
            'direction_id' => 'required|exists:organisation_directions,id',
            'service_id' => 'required_if:niveau_rattachement,service,unite|nullable|exists:organisation_services,id',
            'unite_id' => 'required_if:niveau_rattachement,unite|nullable|exists:organisation_unites,id',
            'contacts' => 'nullable|array',
            'contacts.*.type_contact' => 'required|string',
            'contacts.*.valeur' => 'required|string',
        ];
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function messages(): array
    {
        return [
            'matricule.required' => 'Le matricule est obligatoire.',
            'matricule.unique' => 'Ce matricule est déjà utilisé.',
            'nom.required' => 'Le nom est obligatoire.',
            'prenom.required' => 'Le prénom est obligatoire.',
            'niveau_rattachement.required' => 'Le niveau de rattachement est obligatoire.',
            'direction_id.required_if' => 'La direction est obligatoire.',
            'service_id.required_if' => 'Le service est obligatoire.',
            'unite_id.required_if' => 'L\'unité est obligatoire.',
        ];
    }
}
