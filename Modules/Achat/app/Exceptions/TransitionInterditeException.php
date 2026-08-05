<?php

namespace Modules\Achat\Exceptions;

/**
 * Transition de la machine à états refusée (SFD §1.4). 409 : l'état du
 * document ne permet pas l'opération (PUT/DELETE hors brouillon, validation
 * d'un bon déjà validé, annulation d'un bon déjà réceptionné…).
 */
class TransitionInterditeException extends AchatException
{
    public function status(): int
    {
        return 409;
    }

    public static function pour(string $document, string $depuis, string $vers): self
    {
        return new self("Transition interdite pour {$document} : {$depuis} → {$vers}.");
    }

    /** Annulation refusée : le bon a déjà reçu des livraisons (SFD §7.5). */
    public static function avecReceptions(string $document): self
    {
        return new self(
            "Le bon {$document} a déjà reçu des livraisons : il ne peut plus être annulé. "
            .'Utilisez la clôture du reliquat.'
        );
    }

    /** Écriture refusée sur un document qui n'est plus un brouillon. */
    public static function documentVerrouille(string $document, string $statut): self
    {
        return new self(
            "Le bon {$document} n'est plus modifiable (statut : {$statut})."
        );
    }
}
