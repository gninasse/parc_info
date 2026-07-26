<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Models\StockInventaireLigne;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

class InventaireService
{
    use GeneratesDocumentNumbers;

    public function __construct(
        protected FifoService $fifoService
    ) {}

    public function creerInventaire(int $magasinId, int $userId): StockInventaire
    {
        return DB::transaction(function () use ($magasinId, $userId) {
            $magasin = Magasin::findOrFail($magasinId);

            if (! $magasin->est_actif) {
                throw new \Exception("Le magasin sélectionné n'est pas actif.");
            }

            // Vérifier s'il y a déjà un inventaire en cours (BROUILLON) dans ce magasin
            $encours = StockInventaire::where('magasin_id', $magasinId)
                ->where('statut', 'BROUILLON')
                ->exists();

            if ($encours) {
                throw new \Exception('Un inventaire est déjà en cours dans ce magasin.');
            }

            $numero = $this->genererNumero('INVENTAIRE', 'INV');

            $inventaire = StockInventaire::create([
                'numero_inventaire' => $numero,
                'magasin_id' => $magasinId,
                'date_inventaire' => now()->toDateString(),
                'statut' => 'BROUILLON',
                'nombre_articles' => 0,
                'nombre_ecarts' => 0,
                'created_by' => $userId,
            ]);

            // Figer le stock actuel
            $stocks = StockArticleMagasin::where('magasin_id', $magasinId)->get();
            $compteur = 0;

            foreach ($stocks as $stock) {
                // Calculer le coût moyen unitaire actuel pour valoriser l'écart potentiel
                $valeur = $this->fifoService->calculerValeur($magasinId, $stock->article_id);
                $coutMoyen = $stock->quantite_actuelle > 0 ? round($valeur / $stock->quantite_actuelle, 4) : 0.00;

                if ($coutMoyen <= 0) {
                    $coutMoyen = (float) ($stock->article?->prix_indicatif ?? 0.00);
                }

                StockInventaireLigne::create([
                    'inventaire_id' => $inventaire->id,
                    'article_id' => $stock->article_id,
                    'quantite_theorique' => $stock->quantite_actuelle,
                    'quantite_reelle' => $stock->quantite_actuelle, // Initialisée à la théorique
                    'ecart' => 0,
                    'cout_unitaire_reference' => $coutMoyen,
                ]);

                $compteur++;
            }

            $inventaire->update(['nombre_articles' => $compteur]);

            activity()
                ->performedOn($inventaire)
                ->log('inventory_campaign_created');

            return $inventaire;
        });
    }

    public function enregistrerSaisie(int $inventaireId, array $saisies): void
    {
        DB::transaction(function () use ($inventaireId, $saisies) {
            $inventaire = StockInventaire::findOrFail($inventaireId);

            if ($inventaire->statut !== 'BROUILLON') {
                throw new \Exception("Saisie impossible car l'inventaire n'est plus modifiable.");
            }

            foreach ($saisies as $ligneId => $quantiteReelle) {
                $ligne = StockInventaireLigne::where('inventaire_id', $inventaireId)
                    ->where('id', $ligneId)
                    ->first();

                if ($ligne) {
                    $qteReelle = max(0, (int) $quantiteReelle);
                    $ecart = $qteReelle - $ligne->quantite_theorique;

                    $ligne->update([
                        'quantite_reelle' => $qteReelle,
                        'ecart' => $ecart,
                    ]);
                }
            }
        });
    }

    public function validerInventaire(int $inventaireId, int $userId): void
    {
        DB::transaction(function () use ($inventaireId, $userId) {
            $inventaire = StockInventaire::lockForUpdate()->findOrFail($inventaireId);

            if ($inventaire->statut !== 'BROUILLON') {
                throw new \Exception('Cet inventaire a déjà été clôturé ou annulé.');
            }

            $lignes = $inventaire->lignes;
            $compteurEcarts = 0;

            foreach ($lignes as $ligne) {
                $ecart = $ligne->ecart;

                if ($ecart === 0) {
                    continue;
                }

                $compteurEcarts++;

                if ($ecart > 0) {
                    // Excédent -> Mouvement ENTREE de régularisation
                    $mouvement = StockMouvement::create([
                        'type_mouvement' => 'ENTREE',
                        'article_id' => $ligne->article_id,
                        'magasin_id' => $inventaire->magasin_id,
                        'quantite' => $ecart,
                        'cout_unitaire' => $ligne->cout_unitaire_reference,
                        'type_origine' => 'INVENTAIRE',
                        'origine_id' => $inventaire->id,
                        'reference_document' => $inventaire->numero_inventaire,
                        'motif' => 'Ajustement inventaire + (Excédent)',
                        'valide_at' => now(),
                        'created_by' => $userId,
                    ]);

                    // Créer un lot pour l'entrée excédentaire
                    StockLot::create([
                        'article_id' => $ligne->article_id,
                        'magasin_id' => $inventaire->magasin_id,
                        'quantite_initiale' => $ecart,
                        'quantite_restante' => $ecart,
                        'cout_unitaire' => $ligne->cout_unitaire_reference,
                        'date_entree' => now()->toDateString(),
                        'mouvement_id' => $mouvement->id,
                    ]);

                    // Mettre à jour la fiche stock
                    $stockArticle = StockArticleMagasin::firstOrNew([
                        'magasin_id' => $inventaire->magasin_id,
                        'article_id' => $ligne->article_id,
                    ]);
                    $stockArticle->quantite_actuelle += $ecart;
                    $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($inventaire->magasin_id, $ligne->article_id);
                    $stockArticle->save();

                    // Lier le mouvement à la ligne d'inventaire
                    $ligne->update(['mouvement_id' => $mouvement->id]);

                } elseif ($ecart < 0) {
                    // Déficit -> Mouvement SORTIE de régularisation (FIFO)
                    $deficit = abs($ecart);

                    $mouvement = StockMouvement::create([
                        'type_mouvement' => 'SORTIE',
                        'article_id' => $ligne->article_id,
                        'magasin_id' => $inventaire->magasin_id,
                        'quantite' => $deficit,
                        'cout_unitaire' => $ligne->cout_unitaire_reference, // Sera mis à jour après consommation moyenne
                        'type_origine' => 'INVENTAIRE',
                        'origine_id' => $inventaire->id,
                        'reference_document' => $inventaire->numero_inventaire,
                        'motif' => 'Ajustement inventaire - (Déficit)',
                        'valide_at' => now(),
                        'created_by' => $userId,
                    ]);

                    // Consommer les lots FIFO
                    $consumptions = $this->fifoService->consommerLots($inventaire->magasin_id, $ligne->article_id, $deficit, $mouvement->id);

                    // Calculer le coût unitaire réel des éléments manquants
                    $coutTotal = 0.0;
                    foreach ($consumptions as $c) {
                        $coutTotal += $c['quantite_consommee'] * $c['cout_unitaire'];
                    }
                    $coutMoyen = round($coutTotal / $deficit, 4);
                    $mouvement->update(['cout_unitaire' => $coutMoyen]);

                    // Mettre à jour la fiche stock
                    $stockArticle = StockArticleMagasin::where('magasin_id', $inventaire->magasin_id)
                        ->where('article_id', $ligne->article_id)
                        ->first();

                    if ($stockArticle) {
                        $stockArticle->quantite_actuelle -= $deficit;
                        $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($inventaire->magasin_id, $ligne->article_id);
                        $stockArticle->save();

                        app(StockArticleService::class)->verifierAlerte($stockArticle);
                    }

                    // Lier le mouvement à la ligne
                    $ligne->update(['mouvement_id' => $mouvement->id]);
                }
            }

            $inventaire->update([
                'statut' => 'VALIDE',
                'nombre_ecarts' => $compteurEcarts,
                'valide_par' => $userId,
                'date_cloture' => now(),
            ]);

            activity()
                ->performedOn($inventaire)
                ->log('inventory_campaign_validated');
        });
    }

    public function annulerInventaire(int $inventaireId): void
    {
        DB::transaction(function () use ($inventaireId) {
            $inventaire = StockInventaire::lockForUpdate()->findOrFail($inventaireId);

            if ($inventaire->statut !== 'BROUILLON') {
                throw new \Exception("Cet inventaire ne peut pas être annulé (statut actuel : {$inventaire->statut}).");
            }

            $inventaire->update(['statut' => 'ANNULE']);

            activity()
                ->performedOn($inventaire)
                ->log('inventory_campaign_cancelled');
        });
    }
}
