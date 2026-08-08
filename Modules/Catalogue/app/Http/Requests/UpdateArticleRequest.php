<?php

namespace Modules\Catalogue\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Modules\Catalogue\Models\Article;

class UpdateArticleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('catalogue.articles.update');
    }

    /**
     * C6 : nature et code sont IMMUABLES — absents des règles, ils ne sortent
     * jamais de validated() et sont donc ignorés même si un POST forgé les
     * envoie. Les règles conditionnelles s'appuient sur la nature EN BASE.
     */
    public function rules(): array
    {
        $article = Article::findOrFail($this->route('id'));

        return ArticleRules::pour($article->nature, $article->id, $this->input('marque_id'));
    }

    public function messages(): array
    {
        return ArticleRules::messages();
    }
}
