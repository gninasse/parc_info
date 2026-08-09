<?php

namespace Modules\Achat\Exceptions;

/**
 * Refus de la réception dématérialisée (A-05, M-04 — SFD §7.4).
 *
 * Chaque refus porte un CODE : l'import en masse doit distinguer « clé déjà
 * saisie » (à compter en doublon) de « quantité atteinte » (à compter hors
 * quantité), sans lire le message. Les messages, eux, proposent la sortie —
 * un refus sans issue ne rend service à personne.
 */
class ReceptionLicencesException extends AchatException
{
    public const CODE_DOUBLON = 1;

    public const CODE_TAMPON_COMPLET = 2;

    public const CODE_GARDE = 3;

    /** Lien de correction proposé à l'écran (fiche article du Catalogue). */
    public ?int $articleId = null;

    public function status(): int
    {
        // Tout est un refus de CONTENU (422) : les gardes d'état comprises,
        // l'écran les affiche sur le champ ou en tête de wizard.
        return 422;
    }

    public static function cleVide(): self
    {
        return new self('La clé de licence ne peut pas être vide.', self::CODE_DOUBLON);
    }

    public static function doublonDansLaSession(string $cle): self
    {
        return new self("La clé « {$cle} » est déjà saisie dans cette réception.", self::CODE_DOUBLON);
    }

    public static function doublonDansLeParc(string $cle): self
    {
        return new self(
            "La clé « {$cle} » existe déjà dans le Parc Informatique : vérifiez auprès du fournisseur.",
            self::CODE_DOUBLON
        );
    }

    public static function tamponComplet(int $quantite): self
    {
        return new self(
            "Les {$quantite} clé(s) annoncées sont déjà saisies : revenez au pré-écran pour en réceptionner davantage.",
            self::CODE_TAMPON_COMPLET
        );
    }

    public static function tamponIncomplet(int $manquantes): self
    {
        return new self(
            "{$manquantes} clé(s) manquante(s) : complétez la saisie avant de finaliser.",
            self::CODE_GARDE
        );
    }

    public static function quantiteHorsReste(float $demandee, float $reste): self
    {
        $format = fn (float $valeur) => rtrim(rtrim(number_format($valeur, 2, ',', ' '), '0'), ',');

        return new self(
            "Quantité impossible : {$format($demandee)} demandée(s), reste à livrer {$format($reste)}.",
            self::CODE_GARDE
        );
    }

    /** IA-9 — la garde d'OUVERTURE, avec le lien de correction. */
    public static function logicielNonRattache(string $codeArticle, ?int $articleId): self
    {
        $exception = new self(
            "Impossible de réceptionner : l'article {$codeArticle} n'a pas de logiciel rattaché.",
            self::CODE_GARDE
        );

        $exception->articleId = $articleId;

        return $exception;
    }

    public static function pasUneLicence(string $designation): self
    {
        return new self(
            "La ligne « {$designation} » n'est pas une licence : elle se réceptionne au magasin.",
            self::CODE_GARDE
        );
    }

    public static function pasUnePrestation(string $designation): self
    {
        return new self(
            "La ligne « {$designation} » n'est pas une prestation : le service fait ne s'y applique pas.",
            self::CODE_GARDE
        );
    }

    public static function ligneSoldee(string $designation): self
    {
        return new self("La ligne « {$designation} » est déjà entièrement livrée.", self::CODE_GARDE);
    }

    public static function serviceDejaFait(string $designation): self
    {
        return new self("Le service fait est déjà constaté pour « {$designation} ».", self::CODE_GARDE);
    }

    public static function bonNonReceptionnable(string $document, string $statutLabel): self
    {
        return new self(
            "Le bon {$document} n'attend plus de livraison (statut : {$statutLabel}).",
            self::CODE_GARDE
        );
    }

    public static function sessionClose(string $statut): self
    {
        return new self(
            "Cette session de saisie est close (statut : {$statut}) : ouvrez-en une nouvelle depuis la fiche.",
            self::CODE_GARDE
        );
    }
}
