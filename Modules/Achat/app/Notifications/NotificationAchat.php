<?php

namespace Modules\Achat\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * D-22 — une notification du module Achat.
 *
 * Une seule classe pour les quatre types plutôt que quatre classes : le
 * contenu ne diffère que par un titre et une phrase, et multiplier les
 * classes multiplierait les endroits où une formulation peut dériver.
 *
 * Les canaux sont décidés à la CONSTRUCTION, d'après les préférences de
 * chaque destinataire : la même notification peut partir en cloche seule
 * pour l'un, en cloche et courriel pour l'autre.
 *
 * Aucune logique métier ici (exigence D-22) : la notification lit ce qu'on
 * lui a donné, elle ne va rien chercher en base. C'est ce qui garantit
 * qu'elle ne peut pas, en s'exécutant, modifier ce qu'elle annonce.
 */
class NotificationAchat extends Notification
{
    use Queueable;

    public function __construct(
        private readonly string $type,
        private readonly array $donnees,
        private readonly bool $parMail = true,
        private readonly bool $parCloche = true,
    ) {}

    public function via(object $notifiable): array
    {
        $canaux = [];

        if ($this->parCloche) {
            $canaux[] = 'database';
        }

        // Pas d'adresse, pas de courriel : inutile de faire échouer l'envoi
        // pour un compte technique sans messagerie.
        if ($this->parMail && filled($notifiable->email ?? null)) {
            $canaux[] = 'mail';
        }

        return $canaux;
    }

    /** Le contenu de la cloche : court, avec le lien vers le bon concerné. */
    public function toDatabase(object $notifiable): array
    {
        return [
            'module' => 'achat',
            'type' => $this->type,
            'titre' => $this->donnees['titre'],
            'message' => $this->donnees['message'],
            'bon_id' => $this->donnees['bon_id'] ?? null,
            'url' => $this->url(),
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('[CHU-YO Achat] '.$this->donnees['titre'])
            ->greeting('Bonjour '.($notifiable->name ?? '').',')
            ->line($this->donnees['message']);

        if ($this->url() !== null) {
            $message->action('Ouvrir le bon de commande', $this->url());
        }

        return $message
            ->line('Vous pouvez régler vos notifications depuis votre profil.')
            ->salutation('— Module Achat, CHU-YO');
    }

    private function url(): ?string
    {
        $bonId = $this->donnees['bon_id'] ?? null;

        if ($bonId === null) {
            return route('achat.reliquats.index');
        }

        return route('achat.bons-commande.show', $bonId);
    }
}
