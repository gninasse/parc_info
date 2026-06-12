<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeLicenceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:100|unique:parc_info_types_licences,code',
            'libelle' => 'required|string|max:255',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Ce code existe déjà',
            'libelle.required' => 'Le libellé est obligatoire',
        ];
    }
}
