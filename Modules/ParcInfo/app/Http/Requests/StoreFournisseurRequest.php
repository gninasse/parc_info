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
            'nom' => 'required|string|max:255',
            'type' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'telephone' => 'nullable|string|max:50',
            'adresse' => 'nullable|string',
            'conditions_paiement' => 'nullable|string|max:255',
            'delai_livraison' => 'nullable|string|max:100',
            'fiabilite_score' => 'nullable|numeric|min:0|max:100',
            'est_actif' => 'boolean',
        ];
    }
}
