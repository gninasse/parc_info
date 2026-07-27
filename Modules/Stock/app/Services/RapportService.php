<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Collection;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Models\StockTransfert;

/**
 * F8 — Les quatre rapports du module, filtrables et exportables en PDF.
 *
 * Les agrégations et libellés sont résolus en PHP pour rester portables
 * (PostgreSQL en exploitation, SQLite en test).
 */
class RapportService
{
    public function __construct(protected AchatIntegrationInterface $achat) {}

    /** Rapport des entrées (filtres : magasin, type, article, période). */
    public function entrees(array $filtres = []): Collection
    {
        return $this->mouvements(
            ['ENTREE', 'REGULARISATION_PLUS', 'TRANSFERT_ENTRANT', 'INVENTAIRE_PLUS'],
            $filtres
        );
    }

    /** Rapport des sorties (filtres : magasin, type, article, période). */
    public function sorties(array $filtres = []): Collection
    {
        return $this->mouvements(
            ['SORTIE', 'REGULARISATION_MOINS', 'TRANSFERT_SORTANT', 'INVENTAIRE_MOINS'],
            $filtres
        );
    }

    /** Rapport des transferts (filtres : source, destination, statut, période). */
    public function transferts(array $filtres = []): Collection
    {
        $query = StockTransfert::with(['magasinSource', 'magasinDestination'])
            ->when($filtres['magasin_source_id'] ?? null, fn ($q, $v) => $q->where('magasin_source_id', $v))
            ->when($filtres['magasin_destination_id'] ?? null, fn ($q, $v) => $q->where('magasin_destination_id', $v))
            ->when($filtres['statut'] ?? null, fn ($q, $v) => $q->where('statut', $v))
            ->when($filtres['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtres['date_fin'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest();

        $transferts = $query->get();
        $articles = $this->achat->articlesParIds($transferts->pluck('article_id')->unique()->all());

        return $transferts->map(fn (StockTransfert $transfert) => [
            'numero' => $transfert->numero_transfert,
            'article' => $articles[$transfert->article_id]['designation'] ?? "Article #{$transfert->article_id}",
            'source' => $transfert->magasinSource->libelle,
            'destination' => $transfert->magasinDestination->libelle,
            'quantite' => $transfert->quantite,
            'statut' => $transfert->statut_label,
            'date' => $transfert->created_at->format('d/m/Y H:i'),
        ]);
    }

    /** Rapport du stock par magasin (filtres : magasin, statut d'alerte). */
    public function stock(array $filtres = []): Collection
    {
        $stocks = StockArticleMagasin::with('magasin')
            ->when($filtres['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->orderBy('magasin_id')
            ->get();

        $articles = $this->achat->articlesParIds($stocks->pluck('article_id')->unique()->all());

        $lignes = $stocks->map(function (StockArticleMagasin $stock) use ($articles) {
            $article = $articles[$stock->article_id] ?? null;
            $seuil = (int) ($article['seuil_alerte'] ?? 0);

            return [
                'magasin' => $stock->magasin->libelle,
                'code_article' => $article['code_article'] ?? '-',
                'article' => $article['designation'] ?? "Article #{$stock->article_id}",
                'quantite' => $stock->quantite_actuelle,
                'seuil' => $seuil,
                'statut_alerte' => $stock->statutAlerte($seuil),
                'valeur_fifo' => (float) $stock->valeur_stock_fifo,
            ];
        });

        if (filled($filtres['statut_alerte'] ?? null)) {
            $lignes = $lignes->where('statut_alerte', $filtres['statut_alerte'])->values();
        }

        return $lignes;
    }

    /** @param  list<string>  $types */
    protected function mouvements(array $types, array $filtres): Collection
    {
        $query = StockMouvement::with('magasin')
            ->whereIn('type_mouvement', $types)
            ->when($filtres['magasin_id'] ?? null, fn ($q, $v) => $q->where('magasin_id', $v))
            ->when($filtres['type_mouvement'] ?? null, fn ($q, $v) => $q->where('type_mouvement', $v))
            ->when($filtres['article_id'] ?? null, fn ($q, $v) => $q->where('article_id', $v))
            ->when($filtres['date_debut'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))
            ->when($filtres['date_fin'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest();

        $mouvements = $query->get();
        $articles = $this->achat->articlesParIds($mouvements->pluck('article_id')->unique()->all());

        return $mouvements->map(fn (StockMouvement $mouvement) => [
            'numero' => $mouvement->numero_mouvement,
            'type' => $mouvement->type_label,
            'origine' => $mouvement->type_origine,
            'article' => $articles[$mouvement->article_id]['designation'] ?? "Article #{$mouvement->article_id}",
            'magasin' => $mouvement->magasin->libelle,
            'quantite' => $mouvement->quantite,
            'cout_unitaire' => (float) $mouvement->cout_unitaire,
            'valeur' => round($mouvement->quantite * (float) $mouvement->cout_unitaire, 2),
            'date' => $mouvement->created_at->format('d/m/Y H:i'),
        ]);
    }
}
