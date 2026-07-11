<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBonRepartitionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_bon' => ['required', 'date'],
            'fournisseur_id' => ['nullable', 'exists:parc_info_fournisseurs,id'],
            'observation' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date_bon.required' => 'La date du bon est obligatoire.',
            'date_bon.date' => 'La date du bon doit être une date valide.',
        ];
    }
}
