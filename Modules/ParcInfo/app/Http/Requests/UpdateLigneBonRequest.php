<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLigneBonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type_cible' => ['required', 'in:DIRECTION,SERVICE'],
            'direction_id' => ['nullable', 'exists:organisation_directions,id'],
            'service_id' => ['nullable', 'exists:organisation_services,id'],
            'nom_receptionniste' => ['nullable', 'string', 'max:255'],
            'date_livraison' => ['nullable', 'date'],
            'observation' => ['nullable', 'string'],
        ];
    }
}
