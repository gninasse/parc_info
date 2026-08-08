<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalogue\Models\Categorie;

class StoreCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogue.categories.store');
    }

    public function rules(): array
    {
        return [
            'libelle' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:catalogue_categories,id',
                function (string $attribute, mixed $value, \Closure $fail) {
                    $parent = Categorie::find($value);

                    if ($parent && $parent->parent_id !== null) {
                        $fail("Une sous-catégorie ne peut pas avoir d'enfants (profondeur maximale : 2 niveaux).");
                    }

                    if ($parent && ! $parent->est_actif) {
                        $fail('La catégorie parente doit être active.');
                    }
                },
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'libelle.required' => 'Le libellé est obligatoire.',
            'libelle.max' => 'Le libellé ne peut pas dépasser 255 caractères.',
            'parent_id.exists' => 'La catégorie parente est introuvable.',
        ];
    }
}
