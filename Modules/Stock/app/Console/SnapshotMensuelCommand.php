<?php

namespace Modules\Stock\Console;

use Illuminate\Console\Command;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Services\SnapshotService;

/**
 * RG-F7-06 — Snapshot mensuel automatique (planifié le 1er du mois à 00h05).
 */
class SnapshotMensuelCommand extends Command
{
    protected $signature = 'stock:snapshot-mensuel';

    protected $description = 'Crée la photographie mensuelle de valorisation du stock (FIFO)';

    public function handle(SnapshotService $snapshotService): int
    {
        try {
            $snapshot = $snapshotService->creer('MENSUEL');
        } catch (RegleMetierException $e) {
            // Déjà créé pour ce mois : la commande reste idempotente.
            $this->warn($e->getMessage());

            return self::SUCCESS;
        }

        $this->info("Snapshot {$snapshot->reference} créé — valeur totale : {$snapshot->valeur_totale_globale} FCFA.");

        return self::SUCCESS;
    }
}
