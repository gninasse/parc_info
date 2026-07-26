<?php

namespace Modules\Stock\Emails;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Stock\Models\StockArticleMagasin;

class LowStockAlertMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public StockArticleMagasin $stock) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Alerte : Niveau de stock critique pour {$this->stock->article?->designation}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'stock::emails.low_stock_alert',
        );
    }
}
