<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('fournisseur');

        return [
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('parc_info_fournisseurs', 'code')->ignore($id),
            ],
            'nom' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parc_info_fournisseurs', 'nom')->ignore($id),
            ],
            'type' => 'nullable|string|max:100',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('parc_info_fournisseurs', 'email')->ignore($id),
            ],
            'telephone' => 'nullable|string|max:20',
            'adresse' => 'nullable|string|max:255',
            'code_postal' => 'nullable|string|max:10',
            'ville' => 'nullable|string|max:100',
            'pays' => 'nullable|string|max:100',
            'conditions_paiement' => 'nullable|string|max:255',
            'delai_livraison' => 'nullable|string|max:100',
            'fiabilite_score' => 'nullable|numeric|min:0|max:100',
            'est_actif' => 'boolean',
        ];
    }
}
