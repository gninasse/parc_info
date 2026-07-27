<?php

namespace Modules\Stock\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Services\EntreeStockService;

/**
 * Reprise initiale des quantités de consommables dans le module Stock
 * (lot L2 du CDC — EF-STK-05).
 *
 * À exécuter AVANT les migrations qui suppriment les compteurs
 * achat_articles.stock_actuel et parc_info_consommables.quantite_stock_actuel.
 *
 * Source de vérité : parc_info_consommables.quantite_stock_actuel (rapproché
 * par article_id puis par code) ; repli sur achat_articles.stock_actuel pour
 * les consommables sans fiche ParcInfo. Chaque quantité reprise produit une
 * REGULARISATION_PLUS + lot FIFO dans le magasin de réception.
 */
class ReprendreStockInitialCommand extends Command
{
    private const MOTIF = 'Reprise initiale — migration Stock v2 (EF-STK-05)';

    protected $signature = 'stock:reprise-initiale {--magasin= : Code du magasin de reprise (défaut: stock.magasin_reception_defaut)} {--dry-run : Simule sans écrire}';

    protected $description = 'Reprend les compteurs de consommables (ParcInfo/Achat) en lots FIFO dans le module Stock';

    public function handle(EntreeStockService $entreeStockService): int
    {
        $dryRun = (bool) $this->option('dry-run');

        // Idempotence : une reprise déjà exécutée ne doit pas se rejouer.
        if (StockMouvement::where('motif', self::MOTIF)->exists()) {
            $this->error('Une reprise initiale a déjà été exécutée (mouvements présents). Abandon.');

            return self::FAILURE;
        }

        $codeMagasin = $this->option('magasin') ?: config('stock.magasin_reception_defaut', 'MAG-PRINCIPAL');
        $magasin = Magasin::where('code', $codeMagasin)->first();

        if (! $magasin || ! $magasin->estActif()) {
            $this->error("Magasin {$codeMagasin} introuvable ou inactif.");

            return self::FAILURE;
        }

        $plan = $this->construirePlan();
        $this->signalerFichesNonRapprochees();

        if ($plan === []) {
            $this->info('Aucune quantité à reprendre.');

            return self::SUCCESS;
        }

        $this->table(
            ['Article', 'Code', 'Source', 'Quantité', 'Coût unitaire'],
            array_map(fn (array $ligne) => [
                $ligne['designation'], $ligne['code_article'], $ligne['source'],
                $ligne['quantite'], $ligne['cout_unitaire'],
            ], $plan)
        );

        $total = array_sum(array_column($plan, 'quantite'));
        $this->info(count($plan)." article(s), {$total} unité(s) au total, magasin {$magasin->code}.");

        if ($dryRun) {
            $this->warn('Mode simulation (--dry-run) : aucune écriture.');

            return self::SUCCESS;
        }

        foreach ($plan as $ligne) {
            $entreeStockService->enregistrerEntree([
                'type_mouvement' => 'REGULARISATION_PLUS',
                'article_id' => $ligne['article_id'],
                'magasin_id' => $magasin->id,
                'quantite' => $ligne['quantite'],
                'cout_unitaire' => $ligne['cout_unitaire'],
                'motif' => self::MOTIF,
            ], (int) (DB::table('users')->orderBy('id')->value('id')));

            // Fiabilise le lien fiche ParcInfo ↔ article pour la suite.
            if ($ligne['consommable_id'] !== null) {
                DB::table('parc_info_consommables')
                    ->where('id', $ligne['consommable_id'])
                    ->update(['article_id' => $ligne['article_id']]);
            }
        }

        $this->info('Reprise terminée : '.count($plan).' article(s) repris en lots FIFO.');

        return self::SUCCESS;
    }

    /**
     * Fiches ParcInfo porteuses de stock mais sans article Achat rapproché :
     * elles ne peuvent pas entrer dans le module Stock (référentiel article
     * = achat_articles) et exigent un arbitrage manuel.
     */
    protected function signalerFichesNonRapprochees(): void
    {
        if (! Schema::hasColumn('parc_info_consommables', 'quantite_stock_actuel')) {
            return;
        }

        $codesArticles = DB::table('achat_articles')->whereNull('deleted_at')->pluck('code_article');

        $orphelines = DB::table('parc_info_consommables')
            ->where('quantite_stock_actuel', '>', 0)
            ->whereNull('article_id')
            ->whereNotIn('code', $codesArticles)
            ->get(['code', 'nom', 'quantite_stock_actuel']);

        foreach ($orphelines as $fiche) {
            $this->warn(
                "IGNORÉ : {$fiche->code} — {$fiche->nom} ({$fiche->quantite_stock_actuel} unité(s)) : "
                .'aucun article Achat correspondant. Créez l\'article au catalogue puis relancez la reprise.'
            );
        }
    }

    /**
     * @return list<array{article_id:int,code_article:string,designation:string,quantite:int,cout_unitaire:float,source:string,consommable_id:?int}>
     */
    protected function construirePlan(): array
    {
        $plan = [];
        $articles = DB::table('achat_articles')
            ->where('type_article', 'consommable')
            ->whereNull('deleted_at')
            ->get(['id', 'code_article', 'designation', 'prix_indicatif']);

        $colonneCompteurParcInfo = Schema::hasColumn('parc_info_consommables', 'quantite_stock_actuel');
        $colonneCompteurAchat = Schema::hasColumn('achat_articles', 'stock_actuel');

        foreach ($articles as $article) {
            $fiche = DB::table('parc_info_consommables')
                ->where(function ($query) use ($article) {
                    $query->where('article_id', $article->id)
                        ->orWhere('code', $article->code_article);
                })
                ->first();

            $quantite = 0;
            $source = '—';

            if ($fiche && $colonneCompteurParcInfo && (int) $fiche->quantite_stock_actuel > 0) {
                // Source prioritaire : le compteur ParcInfo (net des consommations).
                $quantite = (int) $fiche->quantite_stock_actuel;
                $source = 'ParcInfo';
            } elseif ($colonneCompteurAchat) {
                $compteurAchat = (int) DB::table('achat_articles')->where('id', $article->id)->value('stock_actuel');

                if ($compteurAchat > 0) {
                    $quantite = $compteurAchat;
                    $source = 'Achat';
                }
            }

            if ($quantite <= 0) {
                continue;
            }

            // Coût : fiche ParcInfo, sinon dernier prix commandé, sinon prix
            // indicatif, sinon 0 (signalé dans le rapport).
            $cout = $fiche->cout_unitaire ?? null;

            if ($cout === null || (float) $cout <= 0) {
                $cout = DB::table('achat_lignes_commande')
                    ->where('article_id', $article->id)
                    ->orderByDesc('id')
                    ->value('prix_unitaire');
            }

            $cout = (float) ($cout ?? $article->prix_indicatif ?? 0);

            $plan[] = [
                'article_id' => (int) $article->id,
                'code_article' => $article->code_article,
                'designation' => $article->designation,
                'quantite' => $quantite,
                'cout_unitaire' => $cout,
                'source' => $source,
                'consommable_id' => $fiche->id ?? null,
            ];
        }

        return $plan;
    }
}
