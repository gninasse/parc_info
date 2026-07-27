<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEntreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.entrees.create');
    }

    public function rules(): array
    {
        return [
            'type_mouvement' => ['required', Rule::in(['ENTREE', 'REGULARISATION_PLUS'])],
            'article_id' => ['required', 'integer', 'exists:achat_articles,id'],
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            // RG-F3-07
            'quantite' => ['required', 'integer', 'min:1'],
            // RG-F3-02
            'cout_unitaire' => ['required', 'numeric', 'min:0'],
            // RG-F3-03
            'motif' => ['nullable', 'string', 'required_if:type_mouvement,REGULARISATION_PLUS'],
            'reference_document' => ['nullable', 'string', 'max:255'],
            'date_entree' => ['nullable', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required_if' => 'Le motif est obligatoire pour une régularisation.',
            'quantite.min' => 'La quantité doit être strictement positive.',
        ];
    }
}
