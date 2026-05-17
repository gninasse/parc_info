<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLogicielRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:255|unique:parc_info_logiciels,code',
            'nom' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type_licence_id' => 'required|exists:parc_info_types_licences,id',
            'editeur_id' => 'required|exists:parc_info_editeurs,id',
            'categorie' => 'nullable|string|max:255',
            'est_actif' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }
}
