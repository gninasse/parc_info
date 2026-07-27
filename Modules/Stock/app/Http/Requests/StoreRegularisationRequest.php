<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRegularisationRequest extends FormRequest
{
    public function authorize(): bool
    {
        // RG-F4-04 — réservée aux administrateurs des sorties.
        return $this->user()->can('stock.sorties.admin');
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'integer', 'exists:achat_articles,id'],
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'motif' => ['required', 'string', 'min:5'],
        ];
    }

    public function messages(): array
    {
        return [
            'motif.required' => 'Le motif est obligatoire pour une régularisation.',
        ];
    }
}
