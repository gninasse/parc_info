<?php

namespace Modules\Achat\Listeners;

use Illuminate\Support\Facades\Log;
use Modules\Achat\Events\BonCommandeAnnule;
use Modules\Achat\Events\BonCommandeCloture;
use Modules\Achat\Events\BonCommandeValide;
use Modules\Achat\Events\BordereauLivraisonValide;

/**
 * Trace applicative des événements du module.
 *
 * ENF-TRA-03 — La piste d'audit métier consultable par l'utilisateur est tenue
 * par spatie/laravel-activitylog depuis les services. Ce listener n'alimente
 * que le journal technique d'exploitation.
 *
 * Les listeners du module ne sont pas ShouldQueue : ils s'exécutent dans la
 * transaction de l'action, conformément à PATTERNS §10.
 */
class JournaliserEvenementAchat
{
    public function handle(object $evenement): void
    {
        $contexte = match (true) {
            $evenement instanceof BonCommandeValide,
            $evenement instanceof BonCommandeAnnule,
            $evenement instanceof BonCommandeCloture => [
                'document' => $evenement->bonCommande->numero_commande,
                'statut' => $evenement->bonCommande->statut,
                'montant_ttc' => $evenement->bonCommande->montant_ttc,
                'user_id' => $evenement->userId,
            ],
            $evenement instanceof BordereauLivraisonValide => [
                'document' => $evenement->bordereau->numero_livraison,
                'bon_commande' => $evenement->bordereau->bonCommande->numero_commande,
                'equipements_crees' => count($evenement->equipements),
                'licences_creees' => count($evenement->licences),
                'user_id' => $evenement->userId,
            ],
            default => [],
        };

        Log::info('[Achat] '.class_basename($evenement), $contexte);
    }
}
