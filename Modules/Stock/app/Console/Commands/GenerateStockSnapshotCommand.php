<?php

namespace Modules\Stock\Console\Commands;

use Illuminate\Console\Command;
use Modules\Stock\Services\ValorisationService;

class GenerateStockSnapshotCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:generate-snapshot {--type=MENSUEL : Le type de valorisation (MENSUEL, PONCTUEL)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Génère un instantané (snapshot) de valorisation du stock pour tous les magasins et articles.';

    /**
     * Execute the console command.
     */
    public function handle(ValorisationService $valorisationService): int
    {
        $type = $this->option('type');
        $this->info("Génération du snapshot de valorisation de type '{$type}'...");

        try {
            $snapshot = $valorisationService->genererSnapshot($type, null);
            $this->info('Instantané généré avec succès !');
            $this->info("Référence : {$snapshot->reference}");
            $this->info('Valeur totale globale : '.number_format($snapshot->valeur_totale_globale, 2, ',', ' ').' F CFA');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Erreur lors de la génération : '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
