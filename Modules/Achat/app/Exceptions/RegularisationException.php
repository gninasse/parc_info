<?php

namespace Modules\Achat\Exceptions;

/**
 * Refus d'un geste de régularisation (A15, M-09).
 *
 * La régularisation est une porte encadrée : chaque refus dit POURQUOI la
 * porte ne s'ouvre pas, pour qu'on ne cherche pas à la forcer.
 */
class RegularisationException extends AchatException
{
    public static function pasUneRegularisation(string $document): self
    {
        return new self(
            "Le bon {$document} n'est pas un bon de régularisation : "
            .'les rattachements d\'équipements y sont sans objet.'
        );
    }

    public static function bonNonValide(string $document, string $statutLabel): self
    {
        return new self(
            "Le bon {$document} doit être validé pour recevoir des rattachements "
            ."(statut actuel : {$statutLabel})."
        );
    }

    public static function aucunEquipement(): self
    {
        return new self('Sélectionnez au moins un équipement à rattacher.');
    }

    /** IA-11 — un équipement n'a qu'une commande d'origine. */
    public static function dejaRattache(string $codeInventaire, string $document): self
    {
        return new self(
            "L'équipement {$codeInventaire} est déjà rattaché au bon {$document} : "
            .'un équipement n\'a qu\'une commande d\'origine. Rafraîchissez la liste.'
        );
    }

    public static function equipementIntrouvable(): self
    {
        return new self('Un des équipements sélectionnés n\'existe plus : rafraîchissez la liste.');
    }

    public static function porteFermee(): self
    {
        return new self(
            'Le mode régularisation est désactivé : la dette de l\'intérim est soldée. '
            .'Un administrateur peut le réactiver si un oubli est découvert.'
        );
    }
}
