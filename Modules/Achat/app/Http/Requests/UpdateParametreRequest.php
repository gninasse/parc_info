<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateParametreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achat.parametres.edit');
    }

    public function rules(): array
    {
        return [
            'valeur' => ['required', 'string', 'max:255'],
        ];
    }
}
