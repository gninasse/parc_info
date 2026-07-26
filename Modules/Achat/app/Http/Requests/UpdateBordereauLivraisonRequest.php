<?php

namespace Modules\Achat\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBordereauLivraisonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('achat.bordereaux.edit');
    }

    public function rules(): array
    {
        $bordereau = $this->route('bordereau');
        $bordereauId = is_object($bordereau) ? $bordereau->id : $bordereau;

        return [
            // EF-BL-14 : le rattachement au bon de commande n'est plus modifiable.
            // Toute valeur transmise est ignorée par le service.
            'date_livraison' => ['required', 'date'],
            'ref_bordereau_physique' => [
                'required', 'string', 'max:100',
                Rule::unique('achat_bordereaux_livraison', 'ref_bordereau_physique')->ignore($bordereauId),
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
            'ref_bordereau_physique.unique' => 'Cette référence de bordereau physique existe déjà.',
            'lignes.required' => 'Le bordereau de livraison doit contenir au moins une ligne.',
            'lignes.*.article_id.distinct' => 'Un même article ne peut figurer qu\'une seule fois dans un bordereau.',
            'lignes.*.quantite_livree.min' => 'La quantité reçue ne peut pas être négative.',
        ];
    }
}
