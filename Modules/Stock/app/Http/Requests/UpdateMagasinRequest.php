<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateMagasinRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.magasins.edit');
    }

    public function rules(): array
    {
        // RG-F1-01 : le code est immuable, il n'est pas accepté en entrée.
        return [
            'libelle' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
        ];
    }
}
