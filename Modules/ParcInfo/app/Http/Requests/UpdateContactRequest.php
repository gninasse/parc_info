<?php

namespace Modules\ParcInfo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nom' => 'required_without:prenom|nullable|string|max:100',
            'prenom' => 'required_without:nom|nullable|string|max:100',
            'fonction' => 'nullable|string|max:100',
            'email' => 'nullable|email',
            'telephone' => 'nullable|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required_without' => 'Le nom ou le prénom est obligatoire.',
            'prenom.required_without' => 'Le nom ou le prénom est obligatoire.',
        ];
    }
}
