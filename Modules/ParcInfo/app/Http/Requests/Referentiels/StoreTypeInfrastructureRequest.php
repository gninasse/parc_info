<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeInfrastructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => 'required|string|max:255|unique:parc_info_types_infrastructures,libelle',
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire',
            'libelle.unique' => 'Ce libellé existe déjà',
        ];
    }
}
