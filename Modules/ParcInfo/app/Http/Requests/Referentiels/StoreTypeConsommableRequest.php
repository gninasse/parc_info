<?php

namespace Modules\ParcInfo\Http\Requests\Referentiels;

use Illuminate\Foundation\Http\FormRequest;

class StoreTypeConsommableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:100|unique:parc_info_types_consommables,code',
            'nom' => 'required|string|max:255',
            'categorie' => 'required|in:Impression,Fournitures Bureau,Maintenance,Reseau,Securite,Accessoires',
            'sous_categorie' => 'nullable|string|max:255',
            'unite_stock' => 'required|string|max:50',
            'seul_reapprovisionnement' => 'nullable|integer|min:0',
            'duree_conservation_jours' => 'nullable|integer|min:0',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Le code est obligatoire',
            'code.unique' => 'Ce code existe déjà',
            'nom.required' => 'Le nom est obligatoire',
            'categorie.required' => 'La catégorie est obligatoire',
            'unite_stock.required' => 'L\'unité de stock est obligatoire',
        ];
    }
}
