<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * EF-DOC-06 — Correction AN-17.
 *
 * La version précédente n'effectuait aucun contrôle d'habilitation : tout
 * utilisateur authentifié pouvait joindre une pièce à n'importe quel document.
 */
class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achat.documents.create');
    }

    public function rules(): array
    {
        $tailleMax = config('achat.documents.taille_max_ko', 10240);
        $mimes = implode(',', config('achat.documents.mimes', ['pdf']));

        return [
            'document' => ['required', 'file', "max:{$tailleMax}", "mimes:{$mimes}"],
            'documentable_type' => ['required', Rule::in(['bon_commande', 'bordereau'])],
            'documentable_id' => ['required', 'integer'],
            'nom' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'document.required' => 'Veuillez sélectionner un fichier.',
            'document.max' => 'Le fichier dépasse la taille maximale autorisée de '
                .round(config('achat.documents.taille_max_ko', 10240) / 1024).' Mo.',
            'document.mimes' => 'Format de fichier non autorisé. Formats acceptés : '
                .implode(', ', config('achat.documents.mimes', [])).'.',
            'documentable_type.in' => 'Type de document invalide.',
        ];
    }
}
