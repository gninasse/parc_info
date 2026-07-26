<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'nom' => ['required', 'string', 'max:255', 'min:3'],
            'type' => ['required', 'in:TECHNIQUE,CONSOMMABLE,REBUT'],
            'description' => ['nullable', 'string'],
            'est_actif' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire.',
            'code.unique' => 'Ce code de magasin existe déjà.',
            'nom.required' => 'Le libellé est obligatoire.',
            'nom.min' => 'Le libellé doit faire au moins 3 caractères.',
            'type.required' => 'Le type de magasin est obligatoire.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'est_actif' => $this->has('est_actif'),
        ]);
    }
}
