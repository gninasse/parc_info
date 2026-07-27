<?php

namespace Modules\Stock\Notifications;

use Illuminate\Notifications\Notification;
use Modules\Stock\Models\StockTransfert;

/**
 * F8 — Notification in-app adressée aux responsables du magasin destination
 * lorsqu'un transfert est créé en attente de validation.
 */
class TransfertEnAttente extends Notification
{
    public function __construct(protected StockTransfert $transfert) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titre' => 'Transfert en attente de validation',
            'message' => sprintf(
                'Le transfert %s (%d unité(s)) vers le magasin %s attend votre validation.',
                $this->transfert->numero_transfert,
                $this->transfert->quantite,
                $this->transfert->magasinDestination->libelle
            ),
            'transfert_id' => $this->transfert->id,
            'numero_transfert' => $this->transfert->numero_transfert,
            'url' => route('stock.transferts.index'),
        ];
    }
}
