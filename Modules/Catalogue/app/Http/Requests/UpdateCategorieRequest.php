<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalogue\Models\Categorie;

class UpdateCategorieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogue.categories.update');
    }

    public function rules(): array
    {
        $id = (int) $this->route('id');

        return [
            'libelle' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'exists:catalogue_categories,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($id) {
                    if ((int) $value === $id) {
                        $fail('Une catégorie ne peut pas être son propre parent.');

                        return;
                    }

                    $parent = Categorie::find($value);

                    if ($parent && $parent->parent_id !== null) {
                        $fail("Une sous-catégorie ne peut pas avoir d'enfants (profondeur maximale : 2 niveaux).");
                    }

                    if ($parent && ! $parent->est_actif) {
                        $fail('La catégorie parente doit être active.');
                    }

                    if (Categorie::where('parent_id', $id)->exists()) {
                        $fail('Cette catégorie possède des sous-catégories : elle ne peut pas devenir elle-même une sous-catégorie.');
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
