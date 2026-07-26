<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEntreeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.entrees.create');
    }

    public function rules(): array
    {
        return [
            'magasin_id' => ['required', 'exists:stock_magasins,id'],
            'article_id' => ['required', 'exists:achat_articles,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'cout_unitaire' => ['required', 'numeric', 'min:0'],
            'reference_document' => ['required', 'string', 'max:100'],
            'motif' => ['nullable', 'string', 'max:500'],
        ];
    }
}
