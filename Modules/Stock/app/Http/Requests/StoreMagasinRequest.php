<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMagasinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.magasins.create');
    }

    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:30', 'unique:stock_magasins,code'],
            // RG-F1-02
            'libelle' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'statut' => ['nullable', Rule::in(['actif', 'inactif'])],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code magasin est déjà utilisé.',
            'libelle.min' => 'Le libellé doit comporter au moins 3 caractères.',
        ];
    }
}
