<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateArticleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('achat.articles.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $articleId = $this->route('article');
        // Si le paramètre de route est un objet, récupérer son ID
        if (is_object($articleId)) {
            $articleId = $articleId->id;
        }

        return [
            'code_article' => [
                'required',
                'string',
                'max:50',
                Rule::unique('achat_articles', 'code_article')->ignore($articleId),
            ],
            'designation' => [
                'required',
                'string',
                'min:3',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'type_article' => [
                'required',
                Rule::in(['equipement', 'consommable', 'licence', 'prestation']),
            ],
            'reference_constructeur' => [
                'nullable',
                'string',
                'max:100',
                // Unique par rapport à la marque, en ignorant le produit en cours
                Rule::unique('achat_articles', 'reference_constructeur')
                    ->where('marque_id', $this->input('marque_id'))
                    ->ignore($articleId),
            ],
            'marque_id' => [
                'required',
                'exists:parc_info_marques,id',
            ],
            'categorie_equipement_id' => [
                'required_if:type_article,equipement',
                'nullable',
                'exists:parc_info_categories_equipements,id',
            ],
            'fournisseur_prefere_id' => [
                'nullable',
                'exists:parc_info_fournisseurs,id',
            ],
            'prix_indicatif' => [
                'nullable',
                'numeric',
                'min:0',
            ],
            'unite_mesure' => [
                'required',
                'string',
                'max:20',
            ],
            'taux_tva' => [
                'required',
                'numeric',
                'min:0',
                'max:100',
            ],
            'compte_comptable' => [
                'nullable',
                'string',
                'max:20',
            ],
            'seuil_alerte' => [
                'required_if:type_article,consommable',
                'nullable',
                'integer',
                'min:0',
            ],
            'duree_validite_mois' => [
                'required_if:type_article,licence',
                'nullable',
                'integer',
                'min:1',
            ],
            'url_fiche_technique' => [
                'nullable',
                'url',
                'max:500',
            ],
            'image' => [
                'nullable',
                'image',
                'max:2048',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'categorie_equipement_id.required_if' => "La catégorie d'équipement est obligatoire pour les Équipements.",
            'seuil_alerte.required_if' => "Le seuil d'alerte est obligatoire pour les Consommables.",
            'duree_validite_mois.required_if' => 'La durée de validité est obligatoire pour les Licences.',
            'reference_constructeur.unique' => 'Cette référence constructeur existe déjà pour cette marque.',
        ];
    }
}
