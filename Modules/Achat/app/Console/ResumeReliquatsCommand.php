<?php

namespace Modules\Achat\Console;

use Illuminate\Console\Command;
use Modules\Achat\Services\AchatParametres;
use Modules\Achat\Services\NotificationsAchat;

/**
 * D-22 — le résumé hebdomadaire des reliquats en retard.
 *
 * Un courriel par reliquat noierait le destinataire et lui apprendrait à les
 * supprimer sans lire. Un résumé par personne, une fois par semaine, se lit —
 * et c'est la seule forme qui produise une action.
 *
 * À planifier dans le programmateur de l'application :
 *
 *     Schedule::command('achat:resume-reliquats')->weeklyOn(1, '07:30');
 *
 * Le lundi matin : les relances fournisseurs se font en début de semaine, et
 * un résumé reçu le vendredi soir n'est jamais traité.
 */
class ResumeReliquatsCommand extends Command
{
    protected $signature = 'achat:resume-reliquats
                            {--seuil= : âge en jours (défaut : paramètre de l\'établissement)}
                            {--simuler : afficher sans envoyer}';

    protected $description = 'Envoie le résumé hebdomadaire des bons non soldés (D-22)';

    public function handle(NotificationsAchat $notifications, AchatParametres $parametres): int
    {
        $seuil = (int) ($this->option('seuil') ?: $parametres->delaiAlerteReliquatJours());

        $this->components->info("Bons non soldés depuis plus de {$seuil} jours");

        if ($this->option('simuler')) {
            $this->components->warn('Simulation : aucun envoi.');

            return self::SUCCESS;
        }

        $notifies = $notifications->resumeReliquats($seuil);

        $this->components->twoColumnDetail(
            'Destinataires notifiés',
            $notifies === 0 ? '<fg=green>aucun reliquat en retard</>' : (string) $notifies
        );

        return self::SUCCESS;
    }
}
