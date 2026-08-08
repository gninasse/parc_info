<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Stock\Http\Requests\Concerns\MessagesValidationFr;

class StoreMagasinRequest extends FormRequest
{
    use MessagesValidationFr;

    public function authorize(): bool
    {
        return true; // permissions portées par le middleware du contrôleur
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            // Un magasin par site (D2) — le code MAG-{CODE_SITE} en découle
            'site_id' => ['required', 'integer', 'exists:organisation_sites,id', 'unique:stock_magasins,site_id'],
            'local_id' => ['nullable', 'integer', 'exists:organisation_locaux,id'],
            'responsable_id' => ['nullable', 'integer', 'exists:grh_dossiers_employes,id'],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'site_id.unique' => 'Ce site a déjà son magasin (règle « un magasin par site »).',
            'site_id.required' => 'Le site est obligatoire.',
            'libelle.required' => 'Le libellé est obligatoire.',
        ];
    }
}
