<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBordereauLivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achat.bordereaux.create');
    }

    public function rules(): array
    {
        return [
            'bon_de_commande_id' => ['required', 'exists:achat_bons_commande,id'],
            'date_livraison' => ['required', 'date'],
            'ref_bordereau_physique' => [
                'required', 'string', 'max:100',
                'unique:achat_bordereaux_livraison,ref_bordereau_physique',
            ],
            'commentaire' => ['nullable', 'string', 'max:1000'],
            'lignes' => ['required', 'array', 'min:1'],
            'lignes.*.article_id' => ['required', 'distinct', 'exists:achat_articles,id'],
            'lignes.*.quantite_livree' => ['required', 'integer', 'min:0'],
            'lignes.*.quantite_refusee' => ['nullable', 'integer', 'min:0'],
            'lignes.*.motif_refus' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'ref_bordereau_physique.unique' => 'Cette référence de bordereau physique existe déjà : cette livraison a probablement déjà été saisie.',
            'lignes.required' => 'Le bordereau de livraison doit contenir au moins une ligne.',
            'lignes.*.article_id.distinct' => 'Un même article ne peut figurer qu\'une seule fois dans un bordereau.',
            'lignes.*.quantite_livree.min' => 'La quantité reçue ne peut pas être négative.',
        ];
    }
}
