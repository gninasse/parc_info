<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\Catalogue\Models\Article;

class StoreArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogue.articles.store');
    }

    public function rules(): array
    {
        return ArticleRules::pour($this->input('nature'), null, $this->input('marque_id')) + [
            'nature' => ['required', Rule::in(Article::NATURES)],
            'code' => ['nullable', 'string', 'max:30', 'unique:catalogue_articles,code'],
        ];
    }

    public function messages(): array
    {
        return ArticleRules::messages() + [
            'nature.required' => 'La nature est obligatoire.',
            'nature.in' => 'Nature inconnue.',
            'code.unique' => 'Ce code article existe déjà (unicité globale, règle C5).',
            'code.max' => 'Le code ne peut pas dépasser 30 caractères.',
        ];
    }
}
