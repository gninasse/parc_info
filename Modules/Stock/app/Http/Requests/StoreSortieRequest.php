<?php

namespace Modules\Stock\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSortieRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('stock.sorties.create');
    }

    public function rules(): array
    {
        return [
            'article_id' => ['required', 'integer', 'exists:achat_articles,id'],
            'magasin_id' => ['required', 'integer', 'exists:stock_magasins,id'],
            'quantite' => ['required', 'integer', 'min:1'],
            'type_cible' => ['required', Rule::in(['EMPLOYE', 'SERVICE', 'DIRECTION', 'UNITE', 'POSTE'])],
            // L'existence de la cible est contrôlée par le service via les contrats.
            'cible_id' => ['required', 'integer'],
            'motif' => ['nullable', 'string'],
            'reference_document' => ['nullable', 'string', 'max:255'],
        ];
    }
}
