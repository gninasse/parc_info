<?php

namespace Modules\Stock\Models\Concerns;

use Modules\Core\Traits\LogsActivityWithModule;

/**
 * LogsActivityWithModule pré-paramétré pour le module (log_name = stock).
 */
trait JournaliseActiviteStock
{
    use LogsActivityWithModule;

    public static function bootJournaliseActiviteStock(): void
    {
        static::$activityModule = 'stock';
    }
}
