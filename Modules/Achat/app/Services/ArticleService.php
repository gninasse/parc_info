<?php

namespace Modules\Achat\Services;

use Exception;
use Illuminate\Database\Eloquent\Builder;
use Modules\Achat\Models\Article;

class ArticleService
{
    /**
     * Liste les articles avec filtres.
     */
    public function lister(array $filtres = [])
    {
        $query = Article::query()->with(['marque', 'categorie', 'fournisseurPrefere']);

        if (! empty($filtres['type_article'])) {
            $query->where('type_article', $filtres['type_article']);
        }

        if (! empty($filtres['marque_id'])) {
            $query->where('marque_id', $filtres['marque_id']);
        }

        if (! empty($filtres['categorie_equipement_id'])) {
            $query->where('categorie_equipement_id', $filtres['categorie_equipement_id']);
        }

        if (isset($filtres['actif']) && $filtres['actif'] !== '') {
            $query->where('actif', (bool) $filtres['actif']);
        }

        if (! empty($filtres['recherche'])) {
            $search = '%'.$filtres['recherche'].'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('code_article', 'like', $search)
                    ->orWhere('designation', 'like', $search)
                    ->orWhere('description', 'like', $search)
                    ->orWhere('reference_constructeur', 'like', $search);
            });
        }

        return $query->orderBy('designation');
    }

    /**
     * Enregistre un nouvel article.
     *
     * @throws Exception
     */
    public function creer(array $data): Article
    {
        $this->validerReglesMetier($data);

        return Article::create($data);
    }

    /**
     * Met à jour un article existant.
     *
     * @throws Exception
     */
    public function modifier(Article $article, array $data): Article
    {
        $this->validerReglesMetier($data, $article->id);
        $article->update($data);

        return $article;
    }

    /**
     * Supprime ou désactive un article.
     *
     * @throws Exception
     */
    public function supprimer(Article $article): void
    {
        // R-ART-10 : Interdire la suppression physique si référencé dans une commande
        if ($article->lignesCommande()->exists()) {
            // Désactiver automatiquement à la place
            $article->update(['actif' => false]);
            throw new Exception("L'article est référencé dans des bons de commande. Il a été désactivé au lieu d'être supprimé.");
        }

        $article->delete();
    }

    /**
     * Duplique un article.
     */
    public function dupliquer(Article $article): Article
    {
        $clone = $article->replicate();
        $clone->code_article = $article->code_article.'-COPY';
        $clone->designation = $article->designation.' (Copie)';
        $clone->save();

        return $clone;
    }

    /**
     * Valide les règles métier du catalogue.
     *
     * @throws Exception
     */
    protected function validerReglesMetier(array $data, ?int $id = null): void
    {
        $type = $data['type_article'] ?? 'equipement';

        // R-ART-04 : Si type_article = 'equipement', categorie_equipement_id est obligatoire
        if ($type === 'equipement' && empty($data['categorie_equipement_id'])) {
            throw new Exception("La catégorie d'équipement est obligatoire pour les articles de type Équipement.");
        }

        // R-ART-05 : Si type_article != 'equipement', alors categorie_equipement_id doit être NULL
        if ($type !== 'equipement' && ! empty($data['categorie_equipement_id'])) {
            throw new Exception("La catégorie d'équipement doit être nulle pour les articles qui ne sont pas des Équipements.");
        }
    }
}
