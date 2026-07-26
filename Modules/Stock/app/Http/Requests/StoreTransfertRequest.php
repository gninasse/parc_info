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
            'magasin_source_id' => ['required', 'exists:stock_magasins,id'],
            'magasin_destination_id' => ['required', 'exists:stock_magasins,id', 'different:magasin_source_id'],
            'article_id' => ['required', 'exists:achat_articles,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'motif_creation' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'magasin_destination_id.different' => 'Le magasin de destination doit être différent du magasin source.',
            'quantite.min' => 'La quantité à transférer doit être d\'au moins 1.',
        ];
    }
}
