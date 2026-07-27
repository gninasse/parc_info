<?php

namespace Modules\Stock\Notifications;

use Illuminate\Notifications\Notification;

/**
 * F8 — Rupture de stock (quantité nulle) constatée après une sortie.
 */
class StockEnRupture extends Notification
{
    public function __construct(
        protected string $articleLibelle,
        protected string $magasinLibelle,
        protected int $articleId,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'titre' => 'Rupture de stock',
            'message' => "L'article {$this->articleLibelle} est en rupture dans le magasin {$this->magasinLibelle}.",
            'article_id' => $this->articleId,
            'niveau' => 'RUPTURE',
            'url' => route('stock.articles.index'),
        ];
    }
}
