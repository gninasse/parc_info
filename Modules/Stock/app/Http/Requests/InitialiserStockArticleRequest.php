<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InitialiserStockArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.articles.admin');
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'integer', 'exists:achat_articles,id'],
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            'quantite_initiale' => ['nullable', 'integer', 'min:0'],
            // RG-F2-03 : exigé par le service dès que quantite_initiale > 0.
            'cout_unitaire' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
