<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTypeInfrastructureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'libelle' => [
                'required',
                'string',
                'max:255',
                Rule::unique('parc_info_types_infrastructures', 'libelle')->ignore($this->route('id')),
            ],
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
