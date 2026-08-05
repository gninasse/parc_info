<?php

namespace Modules\Achat\Models\Concerns;

use Modules\Core\Traits\LogsActivityWithModule;

/**
 * LogsActivityWithModule pré-paramétré pour le module (module = achat).
 */
trait JournaliseActiviteAchat
{
    use LogsActivityWithModule;

    public static function bootJournaliseActiviteAchat(): void
    {
        static::$activityModule = 'achat';
    }
}
