<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBordereauLivraisonRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('achat.bordereaux.edit');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $blId = $this->route('bordereaux') ?? $this->route('bordereau') ?? $this->input('id');
        if (is_object($blId)) {
            $blId = $blId->id;
        }

        return [
            'bon_de_commande_id' => [
                'required',
                'exists:achat_bons_commande,id',
            ],
            'date_livraison' => [
                'required',
                'date',
            ],
            'ref_bordereau_physique' => [
                'required',
                'string',
                'max:100',
                Rule::unique('achat_bordereaux_livraison', 'ref_bordereau_physique')
                    ->ignore($blId),
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
            'lignes.*.quantite_livree' => [
                'required',
                'integer',
                'min:1',
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'ref_bordereau_physique.unique' => 'Cette référence de bordereau physique existe déjà.',
            'lignes.required' => 'Le bordereau de livraison doit contenir au moins une ligne livrée.',
            'lignes.*.quantite_livree.min' => 'La quantité livrée doit être supérieure ou égale à 1.',
        ];
    }
}
