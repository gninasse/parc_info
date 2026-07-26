<?php

namespace Modules\Stock\Console\Commands;

use Illuminate\Console\Command;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Services\StockArticleService;

class CheckStockAlertsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-alerts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Vérifie l\'état des stocks de tous les magasins et signale les articles sous le seuil d\'alerte.';

    /**
     * Execute the console command.
     */
    public function handle(StockArticleService $stockArticleService): int
    {
        $this->info('Vérification des niveaux de stocks...');

        $stocks = StockArticleMagasin::with(['article', 'magasin'])->get();
        $alertCount = 0;

        foreach ($stocks as $stock) {
            $seuil = $stock->article?->seuil_alerte ?? 0;
            if ($stock->quantite_actuelle <= $seuil) {
                $this->warn("Alerte: L'article '{$stock->article?->designation}' dans le magasin '{$stock->magasin?->nom}' est à {$stock->quantite_actuelle} unités (Seuil: {$seuil}).");
                $stockArticleService->verifierAlerte($stock);
                $alertCount++;
            }
        }

        if ($alertCount === 0) {
            $this->info('Tous les niveaux de stock sont corrects.');
        } else {
            $this->warn("{$alertCount} alerte(s) détectée(s) et notifiée(s).");
        }

        return Command::SUCCESS;
    }
}
