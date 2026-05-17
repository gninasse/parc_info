<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsommerConsommableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'quantite' => 'required|integer|min:1',
            'equipement_id' => 'required|exists:parc_info_equipements,id',
            'raison' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ];
    }
}
