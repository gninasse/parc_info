<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achat.articles.create');
    }

    public function rules(): array
    {
        return [
            // EF-CAT-13 : facultatif, généré par le service si absent
            'code_article' => ['nullable', 'string', 'max:50', 'unique:achat_articles,code_article'],
            'designation' => ['required', 'string', 'min:3', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_article' => ['required', Rule::in(array_keys(config('achat.types_articles')))],
            'reference_constructeur' => [
                'nullable', 'string', 'max:100',
                Rule::unique('achat_articles', 'reference_constructeur')
                    ->where('marque_id', $this->input('marque_id'))
                    ->whereNull('deleted_at'),
            ],
            'marque_id' => ['required', 'exists:parc_info_marques,id'],
            'categorie_equipement_id' => [
                'required_if:type_article,equipement', 'nullable',
                'exists:parc_info_categories_equipements,id',
            ],
            'fournisseur_prefere_id' => ['nullable', 'exists:parc_info_fournisseurs,id'],
            'prix_indicatif' => ['nullable', 'numeric', 'min:0'],
            'unite_mesure' => ['required', 'string', 'max:20'],
            'taux_tva' => ['required', 'numeric', 'min:0', 'max:100'],
            'compte_comptable' => ['nullable', 'string', 'max:20'],
            'seuil_alerte' => ['required_if:type_article,consommable', 'nullable', 'integer', 'min:0'],
            'duree_validite_mois' => ['required_if:type_article,licence', 'nullable', 'integer', 'min:1'],
            'url_fiche_technique' => ['nullable', 'url', 'max:500'],
            'image' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'categorie_equipement_id.required_if' => "La catégorie d'équipement est obligatoire pour les Équipements.",
            'seuil_alerte.required_if' => "Le seuil d'alerte est obligatoire pour les Consommables.",
            'duree_validite_mois.required_if' => 'La durée de validité est obligatoire pour les Licences.',
            'reference_constructeur.unique' => 'Cette référence constructeur existe déjà pour cette marque.',
            'code_article.unique' => 'Ce code article est déjà utilisé.',
        ];
    }
}
