<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBonCommandeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('achat.bons_commande.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'fournisseur_id' => [
                'required',
                'exists:parc_info_fournisseurs,id',
            ],
            'date_commande' => [
                'required',
                'date',
            ],
            'commentaire' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'lignes' => [
                'required',
                'array',
                'min:1',
            ],
            'lignes.*.article_id' => [
                'required',
                'exists:achat_articles,id',
            ],
            'lignes.*.quantite' => [
                'required',
                'integer',
                'min:1',
            ],
            'lignes.*.prix_unitaire' => [
                'required',
                'numeric',
                'min:0',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'lignes.required' => 'Le bon de commande doit contenir au moins une ligne.',
            'lignes.min' => 'Le bon de commande doit contenir au moins une ligne.',
            'lignes.*.article_id.required' => "L'article est obligatoire.",
            'lignes.*.article_id.exists' => "L'article sélectionné n'existe pas.",
            'lignes.*.quantite.required' => 'La quantité est obligatoire.',
            'lignes.*.quantite.integer' => 'La quantité doit être un nombre entier.',
            'lignes.*.quantite.min' => 'La quantité doit être supérieure ou égale à 1.',
            'lignes.*.prix_unitaire.required' => 'Le prix unitaire est obligatoire.',
            'lignes.*.prix_unitaire.numeric' => 'Le prix unitaire doit être un nombre.',
            'lignes.*.prix_unitaire.min' => 'Le prix unitaire doit être supérieur ou égal à 0.',
        ];
    }
}
