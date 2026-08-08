<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFournisseurRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogue.fournisseurs.store');
    }

    public function rules(): array
    {
        return [
            'code' => ['nullable', 'string', 'max:30', 'unique:catalogue_fournisseurs,code'],
            'raison_sociale' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'Ce code fournisseur existe déjà.',
            'code.max' => 'Le code ne peut pas dépasser 30 caractères.',
            'raison_sociale.required' => 'La raison sociale est obligatoire.',
            'raison_sociale.max' => 'La raison sociale ne peut pas dépasser 255 caractères.',
            'email.email' => "L'adresse email n'est pas valide.",
            'telephone.max' => 'Le téléphone ne peut pas dépasser 30 caractères.',
        ];
    }
}
