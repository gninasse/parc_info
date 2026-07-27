<?php

namespace Modules\Stock\Notifications;

use Illuminate\Notifications\Notification;

/**
 * F8 — Stock passé sous le seuil d'alerte après une sortie.
 */
class StockSousSeuil extends Notification
{
    public function __construct(
        protected string $articleLibelle,
        protected string $magasinLibelle,
        protected int $articleId,
        protected int $quantite,
        protected int $seuil,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titre' => 'Stock sous le seuil',
            'message' => "L'article {$this->articleLibelle} est sous son seuil d'alerte dans le magasin "
                ."{$this->magasinLibelle} ({$this->quantite} restant(s) pour un seuil de {$this->seuil}).",
            'article_id' => $this->articleId,
            'niveau' => 'ALERTE',
            'url' => route('stock.articles.index'),
        ];
    }
}
