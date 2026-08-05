<?php

namespace Modules\Achat\Exceptions;

/**
 * Soumission au visa refusée : le bon n'est pas complet (SFD §7.1), ou la
 * reprise est tentée par quelqu'un d'autre que l'auteur.
 *
 * 422 : la demande est recevable dans son principe, mais son contenu ne
 * remplit pas les conditions. Les blocages sont conservés pour que l'écran
 * puisse les remonter au champ fautif plutôt qu'en message global.
 */
class SoumissionRefuseeException extends AchatException
{
    /** @var list<array{code: string, message: string, lignes: list<int>}> */
    public array $blocages = [];

    /**
     * @param  list<array{code: string, message: string, lignes: list<int>}>  $blocages
     */
    public static function pour(array $blocages): self
    {
        $exception = new self(
            $blocages === []
                ? 'Ce bon ne peut pas être soumis en l\'état.'
                : implode(' ', array_column($blocages, 'message'))
        );

        $exception->blocages = $blocages;

        return $exception;
    }

    /**
     * La reprise est le retour à soi-même : elle n'a de sens que pour l'auteur
     * du bon. Un tiers qui veut le faire revenir doit utiliser le renvoi
     * motivé, qui laisse une trace nominative (M-06).
     */
    public static function repriseReserveeALAuteur(): self
    {
        return new self(
            'Seul l\'auteur du bon peut reprendre sa soumission. '
            .'Pour le faire revenir en brouillon, utilisez le renvoi motivé.'
        );
    }
}
