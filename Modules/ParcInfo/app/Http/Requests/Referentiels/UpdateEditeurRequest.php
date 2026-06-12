<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditeurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('parc_info_editeurs', 'code')->ignore($this->route('id')),
            ],
            'nom' => 'required|string|max:255',
            'logo_url' => 'nullable|string|max:255',
            'site_web' => 'nullable|url|max:255',
            'email_support' => 'nullable|email|max:255',
            'telephone_support' => 'nullable|string|max:50',
            'est_actif' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Ce code existe déjà',
            'nom.required' => 'Le nom est obligatoire',
        ];
    }
}
