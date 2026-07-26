<?php

namespace Modules\Achat\Http\Controllers\Concerns;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\ActivitylogServiceProvider;

/**
 * Lecture de la piste d'audit d'un document (ENF-TRA-03 / ENF-TRA-05).
 *
 * Le helper activity() retourne un ActivityLogger, destiné à l'écriture : il
 * n'expose aucun scope de requête. La consultation passe obligatoirement par le
 * modèle Activity, résolu depuis la configuration afin de respecter la classe
 * personnalisée déclarée par le projet.
 */
trait ConsulteLeJournal
{
    protected function journalDe(Model $sujet, int $limite = 100): Collection
    {
        $modeleActivite = ActivitylogServiceProvider::determineActivityModel();

        return $modeleActivite::forSubject($sujet)
            ->with('causer')
            ->latest()
            ->limit($limite)
            ->get();
    }
}
