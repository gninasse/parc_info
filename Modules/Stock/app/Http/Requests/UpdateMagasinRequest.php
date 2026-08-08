<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\MessagesValidationFr;

class UpdateMagasinRequest extends FormRequest
{
    use MessagesValidationFr;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Le site est verrouillé en édition (UX MD-MAGASIN : cadenas) : il est
     * absent des règles, donc ignoré même si la requête est forgée — le code
     * MAG-{CODE_SITE} reste stable.
     */
    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'local_id' => ['nullable', 'integer', 'exists:organisation_locaux,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:grh_dossiers_employes,id'],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
        ];
    }
}
