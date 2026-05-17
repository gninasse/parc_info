<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreConsommableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id') ?? $this->route('consommable');

        return [
            'code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parc_info_consommables', 'code')->ignore($id),
            ],
            'nom' => 'required|string|max:255',
            'type_consommable_id' => 'required|exists:parc_info_types_consommables,id',
            'marque_id' => 'nullable|exists:parc_info_marques,id',
            'modele_reference' => 'nullable|string|max:255',
            'fournisseur_principal_id' => 'required|exists:parc_info_fournisseurs,id',
            'cout_unitaire' => 'required|numeric|min:0',
            'quantite_stock_min' => 'required|integer|min:0',
            'quantite_stock_max' => 'required|integer|min:0|gte:quantite_stock_min',
            'est_actif' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }
}
