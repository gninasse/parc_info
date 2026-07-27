<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTransfertRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.transferts.create');
    }

    public function rules(): array
    {
        return [
            'magasin_source_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            // RG-F5-01
            'magasin_destination_id' => ['required', 'integer', 'exists:stock_magasins,id', 'different:magasin_source_id'],
            'article_id' => ['required', 'integer', 'exists:achat_articles,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'motif_creation' => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'magasin_destination_id.different' => 'Les magasins source et destination doivent être distincts.',
        ];
    }
}
