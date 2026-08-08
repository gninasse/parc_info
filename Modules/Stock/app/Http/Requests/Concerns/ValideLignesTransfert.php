<?php

namespace Modules\Stock\Http\Requests\Concerns;

use Illuminate\Validation\Rule;
use Modules\Catalogue\Models\Article;
use Modules\Stock\Models\Magasin;

/**
 * Contrôles de FORME du brouillon de transfert (UX §5.2) : source ≠ cible
 * (doublé par le CHECK en base), magasins actifs, lignes en articles.
 */
trait ValideLignesTransfert
{
    protected function reglesCommunes(): array
    {
        $magasinActif = fn (string $role) => function (string $attribute, $value, \Closure $fail) use ($role) {
            if (! Magasin::query()->where('id', $value)->where('est_actif', true)->exists()) {
                $fail("Le magasin {$role} est désactivé : aucun transfert possible.");
            }
        };

        return [
            'magasin_source_id' => [
                'required', 'integer', Rule::exists('stock_magasins', 'id'), $magasinActif('source'),
            ],
            'magasin_cible_id' => [
                'required', 'integer', Rule::exists('stock_magasins', 'id'),
                'different:magasin_source_id', // contrôle immédiat « identique à la source »
                $magasinActif('cible'),
            ],
            'date_document' => ['required', 'date'],
            'transporte_par_nom' => ['nullable', 'string', 'max:255'],
            'transporte_par_employe_id' => ['nullable', 'integer', Rule::exists('grh_dossiers_employes', 'id')],
            'observation' => ['nullable', 'string'],

            'lignes' => ['array'],
            'lignes.*.article_id' => [
                'required', 'integer',
                function (string $attribute, $value, \Closure $fail) {
                    $article = Article::query()->find($value);

                    if ($article === null) {
                        $fail('Article inconnu au catalogue.');
                    } elseif (! $article->est_stockable) {
                        $fail("L'article « {$article->nom} » n'est pas stockable.");
                    }
                },
            ],
            'lignes.*.quantite' => ['required', 'numeric', 'gt:0'],
        ];
    }

    protected function messagesSpecifiques(): array
    {
        return [
            'magasin_cible_id.different' => 'Le magasin cible est identique à la source.',
            'lignes.*.quantite.gt' => 'La quantité doit être strictement positive.',
        ];
    }
}
