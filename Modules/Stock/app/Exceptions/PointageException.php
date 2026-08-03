<?php

namespace Modules\Stock\Exceptions;

/**
 * Refus de pointage (I17/D14) — messages exacts UX §0.5/§4, 422.
 */
class PointageException extends StockException
{
    public static function aucuneLigneModele(): self
    {
        return new self('Aucune ligne « modèle × N » : ce bon se valide directement, sans pointage.');
    }

    public static function introuvableDansCeMagasin(): self
    {
        return new self('Introuvable dans ce magasin');
    }

    public static function dejaPointe(): self
    {
        return new self('Déjà pointé');
    }

    public static function nonEnStock(): self
    {
        return new self('Unité non « en stock »');
    }

    public static function ligneComplete(int $quantite): self
    {
        return new self("Cette ligne a déjà ses {$quantite} unité(s) pointée(s).");
    }

    public static function modeleDifferent(string $codeInventaire): self
    {
        return new self("L'unité {$codeInventaire} ne correspond pas au modèle de cette ligne.");
    }

    public static function aucunArticleCorrespondant(string $modele): self
    {
        return new self("Aucun article du catalogue ne correspond au modèle « {$modele} » : ajoutez la ligne manuellement.");
    }

    public static function pointageIncomplet(int $manquantes): self
    {
        return new self("{$manquantes} unité(s) restant à pointer avant validation.");
    }
}
