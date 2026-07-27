<?php

namespace Modules\Achat\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\Article;

class ArticleService
{
    /** Requête filtrée du catalogue (EF-CAT-10). */
    public function lister(array $filtres = []): Builder
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

        if (isset($filtres['actif']) && $filtres['actif'] !== '' && $filtres['actif'] !== null) {
            $query->where('actif', filter_var($filtres['actif'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filtres['recherche'])) {
            $recherche = '%'.$filtres['recherche'].'%';

            $query->where(function (Builder $sousRequete) use ($recherche) {
                $sousRequete->where('code_article', 'like', $recherche)
                    ->orWhere('designation', 'like', $recherche)
                    ->orWhere('description', 'like', $recherche)
                    ->orWhere('reference_constructeur', 'like', $recherche);
            });
        }

        return $query->orderBy('designation');
    }

    public function creer(array $donnees): Article
    {
        $donnees = $this->normaliser($donnees);
        $this->validerReglesMetier($donnees);

        return DB::transaction(function () use ($donnees) {
            $article = Article::create($donnees);

            activity()->performedOn($article)->log('Article référencé');

            return $article;
        });
    }

    public function modifier(Article $article, array $donnees): Article
    {
        $donnees = $this->normaliser($donnees);
        $this->validerReglesMetier($donnees);

        return DB::transaction(function () use ($article, $donnees) {
            $article->update($donnees);

            activity()->performedOn($article)->log('Article modifié');

            return $article->refresh();
        });
    }

    /**
     * RG-ART-10 — Un article engagé dans une commande n'est jamais supprimé.
     *
     * @return bool true si l'article a été supprimé, false s'il a été désactivé
     */
    public function supprimer(Article $article): bool
    {
        return DB::transaction(function () use ($article) {
            if (! $article->estSupprimable()) {
                $article->update(['actif' => false]);

                activity()->performedOn($article)->log('Article désactivé (référencé en commande)');

                return false;
            }

            $article->delete();

            activity()->performedOn($article)->log('Article supprimé');

            return true;
        });
    }

    /** RG-ART-11 — Duplication pour accélérer le référencement de variantes. */
    public function dupliquer(Article $article): Article
    {
        return DB::transaction(function () use ($article) {
            $copie = $article->replicate();
            $copie->code_article = $this->codeDisponible($article->code_article.'-COPY');
            $copie->designation = Str::limit($article->designation.' (Copie)', 255, '');
            $copie->reference_constructeur = null; // RG-ART-03 : unique par marque
            $copie->save();

            activity()->performedOn($copie)->log("Article dupliqué depuis {$article->code_article}");

            return $copie;
        });
    }

    /** EF-CAT-13 — Génère un code à partir du type et d'un compteur. */
    public function genererCode(string $typeArticle): string
    {
        $prefixe = match ($typeArticle) {
            'equipement' => 'EQP',
            'consommable' => 'CNS',
            'licence' => 'LIC',
            'prestation' => 'PRE',
            default => 'ART',
        };

        $dernier = Article::withTrashed()
            ->where('code_article', 'like', $prefixe.'-%')
            ->orderByDesc('id')
            ->value('code_article');

        $sequence = $dernier ? ((int) substr($dernier, strrpos($dernier, '-') + 1)) + 1 : 1;

        do {
            $code = sprintf('%s-%05d', $prefixe, $sequence);
            $sequence++;
        } while (Article::withTrashed()->where('code_article', $code)->exists());

        return $code;
    }

    /** Normalise les entrées et applique les valeurs par défaut. */
    protected function normaliser(array $donnees): array
    {
        $type = $donnees['type_article'] ?? 'equipement';

        // EF-CAT-13 : génération automatique si le code n'est pas fourni
        if (empty($donnees['code_article'])) {
            $donnees['code_article'] = $this->genererCode($type);
        }

        $donnees['code_article'] = mb_strtoupper(trim($donnees['code_article']));

        // RG-ART-05 : la catégorie n'a de sens que pour un équipement
        if ($type !== 'equipement') {
            $donnees['categorie_equipement_id'] = null;
        }

        // Seuls les consommables portent un seuil, seules les licences une durée
        if ($type !== 'consommable') {
            $donnees['seuil_alerte'] = 0;
        }

        if ($type !== 'licence') {
            $donnees['duree_validite_mois'] = null;
        }

        if (! isset($donnees['taux_tva']) || $donnees['taux_tva'] === '') {
            $donnees['taux_tva'] = config('achat.taux_tva_defaut', 18.00);
        }

        if (empty($donnees['reference_constructeur'])) {
            $donnees['reference_constructeur'] = null;
        }

        return $donnees;
    }

    /**
     * Règles sémantiques non exprimables en validation de formulaire.
     *
     * @throws RegleMetierException
     */
    protected function validerReglesMetier(array $donnees): void
    {
        $type = $donnees['type_article'] ?? 'equipement';

        // RG-ART-04
        if ($type === 'equipement' && empty($donnees['categorie_equipement_id'])) {
            throw new RegleMetierException(
                "La catégorie d'équipement est obligatoire pour les articles de type Équipement."
            );
        }
    }

    /** Retourne un code libre en suffixant si nécessaire. */
    protected function codeDisponible(string $codeSouhaite): string
    {
        $code = mb_substr(mb_strtoupper($codeSouhaite), 0, 50);
        $suffixe = 1;

        while (Article::withTrashed()->where('code_article', $code)->exists()) {
            $suffixe++;
            $code = mb_substr(mb_strtoupper($codeSouhaite), 0, 46).'-'.$suffixe;
        }

        return $code;
    }
}
