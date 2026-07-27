<?php

namespace Modules\Achat\Services;

use Modules\Achat\Contracts\StockIntegrationInterface;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;

/**
 * Seul point de contact du module Achat avec le module Stock.
 *
 * Correction AN-12 : seuls les articles dont le type alimente le stock
 * physique (config achat.types_avec_stock) sont enregistrés — les équipements
 * et licences donnent lieu à des fiches individuelles dans ParcInfo.
 *
 * La mécanique FIFO (mouvement + lot + projection) appartient au module
 * Stock : ce service se contente de déléguer à EntreeStockService, ligne par
 * ligne, dans la transaction ouverte par WizardValidationService.
 */
class StockIntegrationService implements StockIntegrationInterface
{
    public function estDisponible(): bool
    {
        return config('achat.integration.stock', true)
            && class_exists(\Modules\Stock\Services\EntreeStockService::class)
            && \Nwidart\Modules\Facades\Module::isEnabled('Stock');
    }

    public function enregistrerEntreesDepuisBordereau(BordereauLivraison $bordereau, int $userId): int
    {
        if (! $this->estDisponible()) {
            return 0;
        }

        $typesStockables = config('achat.types_avec_stock', ['consommable']);
        $magasinId = $this->magasinDeReception();
        $entreeStock = app(\Modules\Stock\Services\EntreeStockService::class);
        $enregistrees = 0;

        foreach ($bordereau->lignesLivraison as $ligne) {
            $article = $ligne->article;

            if (! in_array($article->type_article, $typesStockables, true)) {
                continue;
            }

            // RGC-06 — la valeur d'entrée est le prix commandé, jamais le
            // prix indicatif du catalogue.
            $ligneCommande = LigneCommande::where('bon_de_commande_id', $bordereau->bon_de_commande_id)
                ->where('article_id', $article->id)
                ->first();

            $entreeStock->enregistrerEntree([
                'type_mouvement' => 'ENTREE',
                'article_id' => $article->id,
                'magasin_id' => $magasinId,
                'quantite' => $ligne->quantite_livree,
                'cout_unitaire' => $ligneCommande ? (float) $ligneCommande->prix_unitaire : 0.0,
                'type_origine' => 'BL',
                'origine_id' => $bordereau->id,
                'reference_document' => $bordereau->numero_livraison,
                'motif' => "Entrée automatique via validation du BL n° {$bordereau->numero_livraison}",
                'date_entree' => $bordereau->date_livraison?->toDateString(),
            ], $userId);

            $enregistrees++;
        }

        return $enregistrees;
    }

    /**
     * Magasin de réception paramétré (stock.magasin_reception_defaut),
     * créé par le seeder du module Stock — jamais à la volée.
     */
    protected function magasinDeReception(): int
    {
        $code = config('stock.magasin_reception_defaut', 'MAG-PRINCIPAL');

        $magasin = \Modules\Stock\Models\Magasin::where('code', $code)->first()
            ?? \Modules\Stock\Models\Magasin::actif()->orderBy('id')->first();

        if (! $magasin) {
            throw new \Modules\Achat\Exceptions\RegleMetierException(
                'Aucun magasin de réception n\'est configuré dans le module Stock.'
            );
        }

        return $magasin->id;
    }
}
