<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Core\Models\User;
use Modules\Stock\Exceptions\RegleMetierException;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockInventaire;
use Modules\Stock\Models\StockInventaireLigne;
use Modules\Stock\Models\StockLot;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

/**
 * Inventaires physiques (F6) — snapshot théorique figé, saisie partielle,
 * validation atomique des écarts.
 */
class InventaireService
{
    use GeneratesDocumentNumbers;

    public function __construct(protected FifoService $fifoService) {}

    /**
     * RG-F6-01/02 — Ouvre une campagne et fige le stock théorique.
     *
     * @throws RegleMetierException
     */
    public function creer(int $magasinId, ?string $dateInventaire, int $userId): StockInventaire
    {
        $magasin = Magasin::find($magasinId);

        if (! $magasin) {
            throw new RegleMetierException('Le magasin sélectionné est introuvable.');
        }

        // RG-F1-03
        if (! $magasin->estActif()) {
            throw new RegleMetierException("Le magasin {$magasin->libelle} est inactif : inventaire impossible.");
        }

        // RG-F6-01
        $enCours = StockInventaire::where('magasin_id', $magasin->id)
            ->where('statut', 'EN_COURS')
            ->exists();

        if ($enCours) {
            throw new RegleMetierException('Un inventaire est déjà en cours sur ce magasin.');
        }

        $stocks = StockArticleMagasin::where('magasin_id', $magasin->id)->get();

        if ($stocks->isEmpty()) {
            throw new RegleMetierException('Aucun article n\'est initialisé dans ce magasin.');
        }

        return DB::transaction(function () use ($magasin, $dateInventaire, $userId, $stocks) {
            $inventaire = StockInventaire::create([
                'numero_inventaire' => $this->genererNumero('INV', 'INV'),
                'magasin_id' => $magasin->id,
                'date_inventaire' => $dateInventaire ?? now()->toDateString(),
                'statut' => 'EN_COURS',
                'nombre_articles' => $stocks->count(),
                'created_by' => $userId,
            ]);

            foreach ($stocks as $stock) {
                // Coût de référence pour un éventuel écart positif : dernier
                // coût FIFO connu de l'article dans ce magasin.
                $coutReference = StockLot::where('article_id', $stock->article_id)
                    ->where('magasin_id', $magasin->id)
                    ->orderByDesc('date_entree')
                    ->orderByDesc('id')
                    ->value('cout_unitaire');

                StockInventaireLigne::create([
                    'inventaire_id' => $inventaire->id,
                    'article_id' => $stock->article_id,
                    'quantite_theorique' => $stock->quantite_actuelle,
                    'cout_unitaire_reference' => $coutReference,
                ]);
            }

            activity()->performedOn($inventaire)->causedBy(User::find($userId))
                ->log("Inventaire {$inventaire->numero_inventaire} ouvert ({$stocks->count()} article(s))");

            return $inventaire;
        });
    }

    /**
     * RG-F6-03 — Sauvegarde partielle des comptages, sans aucun mouvement.
     *
     * @param  array<int|string, int|null>  $comptages  [ligne_id => quantite_reelle]
     *
     * @throws RegleMetierException
     */
    public function saisirComptages(StockInventaire $inventaire, array $comptages): void
    {
        if (! $inventaire->estEnCours()) {
            throw new RegleMetierException('Seul un inventaire en cours peut être saisi.');
        }

        DB::transaction(function () use ($inventaire, $comptages) {
            foreach ($comptages as $ligneId => $quantiteReelle) {
                if ($quantiteReelle === null || $quantiteReelle === '') {
                    continue;
                }

                if ((int) $quantiteReelle < 0) {
                    throw new RegleMetierException('Une quantité comptée ne peut pas être négative.');
                }

                $ligne = StockInventaireLigne::where('inventaire_id', $inventaire->id)
                    ->where('id', $ligneId)
                    ->first();

                if (! $ligne) {
                    throw new RegleMetierException('Ligne d\'inventaire introuvable.');
                }

                $ligne->update([
                    'quantite_reelle' => (int) $quantiteReelle,
                    'ecart' => (int) $quantiteReelle - $ligne->quantite_theorique,
                ]);
            }
        });
    }

    /**
     * RG-F6-04→08 — Validation atomique : chaque écart produit son mouvement
     * d'inventaire et l'ajustement FIFO correspondant.
     *
     * @throws RegleMetierException
     */
    public function valider(StockInventaire $inventaire, int $userId): StockInventaire
    {
        if (! $inventaire->estEnCours()) {
            throw new RegleMetierException('Seul un inventaire en cours peut être validé.');
        }

        // RG-F6-04
        $nonComptees = $inventaire->lignes()->whereNull('quantite_reelle')->count();

        if ($nonComptees > 0) {
            throw new RegleMetierException(
                "{$nonComptees} ligne(s) n'ont pas été comptées : la validation est bloquée."
            );
        }

        return DB::transaction(function () use ($inventaire, $userId) {
            $ecarts = 0;

            foreach ($inventaire->lignes as $ligne) {
                $ecart = $ligne->quantite_reelle - $ligne->quantite_theorique;

                // RG-F6-07
                if ($ecart === 0) {
                    continue;
                }

                $ecarts++;
                $mouvement = $ecart > 0
                    ? $this->creerEcartPositif($inventaire, $ligne, $ecart, $userId)
                    : $this->creerEcartNegatif($inventaire, $ligne, abs($ecart), $userId);

                $ligne->update(['ecart' => $ecart, 'mouvement_id' => $mouvement->id]);
                $this->actualiserProjection($ligne->article_id, $inventaire->magasin_id);
            }

            $inventaire->update([
                'statut' => 'CLOTURE',
                'nombre_ecarts' => $ecarts,
                'valide_par' => $userId,
                'date_cloture' => now(),
            ]);

            activity()->performedOn($inventaire)->causedBy(User::find($userId))
                ->log("Inventaire {$inventaire->numero_inventaire} clôturé ({$ecarts} écart(s))");

            return $inventaire;
        });
    }

    /**
     * RG-F6-09 — Annulation (l'habilitation admin relève de l'appelant).
     *
     * @throws RegleMetierException
     */
    public function annuler(StockInventaire $inventaire, int $userId): StockInventaire
    {
        if (! $inventaire->estEnCours()) {
            throw new RegleMetierException('Seul un inventaire en cours peut être annulé.');
        }

        $inventaire->update(['statut' => 'ANNULE']);

        activity()->performedOn($inventaire)->causedBy(User::find($userId))
            ->log("Inventaire {$inventaire->numero_inventaire} annulé");

        return $inventaire;
    }

    // ── Internes ───────────────────────────────────────────────────────────

    /** RG-F6-05 — Écart positif : mouvement + lot au coût de référence. */
    protected function creerEcartPositif(StockInventaire $inventaire, StockInventaireLigne $ligne, int $quantite, int $userId): StockMouvement
    {
        $cout = (float) ($ligne->cout_unitaire_reference ?? 0);

        $mouvement = StockMouvement::create([
            'numero_mouvement' => $this->genererNumero('BE', 'BE'),
            'type_mouvement' => 'INVENTAIRE_PLUS',
            'article_id' => $ligne->article_id,
            'magasin_id' => $inventaire->magasin_id,
            'quantite' => $quantite,
            'cout_unitaire' => $cout,
            'type_origine' => 'INVENTAIRE',
            'origine_id' => $inventaire->id,
            'reference_document' => $inventaire->numero_inventaire,
            'motif' => "Écart positif constaté à l'inventaire {$inventaire->numero_inventaire}",
            'valide_at' => now(),
            'created_by' => $userId,
        ]);

        StockLot::create([
            'article_id' => $ligne->article_id,
            'magasin_id' => $inventaire->magasin_id,
            'quantite_initiale' => $quantite,
            'quantite_restante' => $quantite,
            'cout_unitaire' => $cout,
            'date_entree' => now()->toDateString(),
            'mouvement_id' => $mouvement->id,
        ]);

        return $mouvement;
    }

    /** RG-F6-06 — Écart négatif : mouvement + consommation FIFO. */
    protected function creerEcartNegatif(StockInventaire $inventaire, StockInventaireLigne $ligne, int $quantite, int $userId): StockMouvement
    {
        $fifo = $this->fifoService->consommerLots($ligne->article_id, $inventaire->magasin_id, $quantite);

        return StockMouvement::create([
            'numero_mouvement' => $this->genererNumero('BS', 'BS'),
            'type_mouvement' => 'INVENTAIRE_MOINS',
            'article_id' => $ligne->article_id,
            'magasin_id' => $inventaire->magasin_id,
            'quantite' => $quantite,
            'cout_unitaire' => round($fifo['cout_total'] / $quantite, 4),
            'type_origine' => 'INVENTAIRE',
            'origine_id' => $inventaire->id,
            'reference_document' => $inventaire->numero_inventaire,
            'motif' => "Écart négatif constaté à l'inventaire {$inventaire->numero_inventaire}",
            'valide_at' => now(),
            'created_by' => $userId,
        ]);
    }

    protected function actualiserProjection(int $articleId, int $magasinId): void
    {
        $stockArticle = StockArticleMagasin::firstOrCreate(
            ['article_id' => $articleId, 'magasin_id' => $magasinId]
        );

        $stockArticle->quantite_actuelle = (int) StockLot::where('article_id', $articleId)
            ->where('magasin_id', $magasinId)
            ->sum('quantite_restante');
        $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);
        $stockArticle->save();
    }
}
