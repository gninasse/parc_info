<?php

namespace Modules\Stock\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\Article;
use Modules\ParcInfo\Models\AffectationConsommable;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\AffectationLicence;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Equipement;
use Modules\ParcInfo\Models\Licence;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\StockArticleMagasin;
use Modules\Stock\Models\StockMouvement;
use Modules\Stock\Traits\GeneratesDocumentNumbers;

class SortieStockService
{
    use GeneratesDocumentNumbers;

    public function __construct(protected FifoService $fifoService) {}

    public function creerSortie(
        int $magasinId,
        int $articleId,
        int $quantite,
        string $typeAffectationParcinfo, // EQUIPEMENT, CONSOMMABLE, LICENCE
        string $typeCible, // EMPLOYE, SERVICE, DIRECTION, UNITE, POSTE
        int $cibleId,
        array $additionalData = [],
        ?int $userId = null
    ): StockMouvement {
        return DB::transaction(function () use ($magasinId, $articleId, $quantite, $typeAffectationParcinfo, $typeCible, $cibleId, $additionalData, $userId) {
            $magasin = Magasin::findOrFail($magasinId);
            $article = Article::findOrFail($articleId);

            // RG-F4-01: Le magasin doit être actif
            if (! $magasin->est_actif) {
                throw new \Exception("Le magasin n'est pas actif.");
            }

            if ($quantite <= 0) {
                throw new \Exception('La quantité de sortie doit être supérieure à 0.');
            }

            // RG-F4-02: Le stock actuel doit être suffisant
            $stockArticle = StockArticleMagasin::where('magasin_id', $magasinId)
                ->where('article_id', $articleId)
                ->lockForUpdate()
                ->first();

            if (! $stockArticle || $stockArticle->quantite_actuelle < $quantite) {
                $actuel = $stockArticle ? $stockArticle->quantite_actuelle : 0;
                throw new \Exception("Stock insuffisant. Disponible : {$actuel}, Demandé : {$quantite}.");
            }

            // 1. Mouvement de sortie
            $mouvement = StockMouvement::create([
                'type_mouvement' => 'SORTIE',
                'article_id' => $articleId,
                'magasin_id' => $magasinId,
                'quantite' => $quantite,
                'cout_unitaire' => 0, // Mis à jour après calcul moyen
                'type_origine' => 'MANUEL',
                'reference_document' => $additionalData['reference_document'] ?? null,
                'motif' => $additionalData['motif'] ?? 'Sortie manuelle',
                'valide_at' => now(),
                'created_by' => $userId,
            ]);

            // Consommer les lots FIFO
            $consumptions = $this->fifoService->consommerLots($magasinId, $articleId, $quantite, $mouvement->id);

            // Calculer le coût moyen
            $coutTotal = 0.0;
            foreach ($consumptions as $c) {
                $coutTotal += $c['quantite_consommee'] * $c['cout_unitaire'];
            }
            $coutMoyen = round($coutTotal / $quantite, 4);
            $mouvement->update(['cout_unitaire' => $coutMoyen]);

            // Mettre à jour le stock
            $stockArticle->quantite_actuelle -= $quantite;
            $stockArticle->derniere_sortie_at = now();
            $stockArticle->valeur_stock_fifo = $this->fifoService->calculerValeur($magasinId, $articleId);
            $stockArticle->save();

            app(StockArticleService::class)->verifierAlerte($stockArticle);

            // 2. Routage / Affectation ParcInfo
            $affectationId = null;

            if ($typeAffectationParcinfo === 'EQUIPEMENT') {
                $equipementId = $additionalData['equipement_id'] ?? null;
                if (! $equipementId) {
                    throw new \Exception("L'identifiant de l'équipement physique est requis pour une affectation EQUIPEMENT.");
                }

                $equipement = Equipement::findOrFail($equipementId);

                // Mettre à jour l'équipement
                $equipement->statut = 'en_service';

                // Appliquer les cibles à l'équipement
                if ($typeCible === 'SERVICE') {
                    $equipement->service_id = $cibleId;
                } elseif ($typeCible === 'DIRECTION') {
                    $equipement->direction_id = $cibleId;
                } elseif ($typeCible === 'UNITE') {
                    $equipement->unite_id = $cibleId;
                }
                $equipement->save();

                // Créer l'AffectationEquipement
                $codeAff = $this->genererNumero('AFFECTATION', 'AFF');
                $affectation = AffectationEquipement::create([
                    'code' => $codeAff,
                    'date_debut' => now(),
                    'equipement_id' => $equipementId,
                    'statut' => true,
                    'type_affectation' => 'PERMANENTE',
                    'type_cible' => $typeCible,
                    'dossier_employe_id' => ($typeCible === 'EMPLOYE') ? $cibleId : null,
                    'direction_id' => ($typeCible === 'DIRECTION') ? $cibleId : null,
                    'service_id' => ($typeCible === 'SERVICE') ? $cibleId : null,
                    'unite_id' => ($typeCible === 'UNITE') ? $cibleId : null,
                ]);

                $affectationId = $affectation->id;

            } elseif ($typeAffectationParcinfo === 'CONSOMMABLE') {
                // Trouver le consommable correspondant au code article dans ParcInfo
                $consommable = Consommable::where('code', $article->code_article)->first();
                if (! $consommable) {
                    throw new \Exception("Consommable non configuré dans le parc informatique pour le code article {$article->code_article}.");
                }

                // Décrémenter le stock dans ParcInfo consommable
                $consommable->quantite_stock_actuel = max(0, $consommable->quantite_stock_actuel - $quantite);
                $consommable->save();

                // Créer l'AffectationConsommable
                $affectation = AffectationConsommable::create([
                    'consommable_id' => $consommable->id,
                    'equipement_id' => $additionalData['equipement_destination_id'] ?? null,
                    'quantite_fournie' => $quantite,
                    'date_affectation' => now(),
                    'notes' => $additionalData['motif'] ?? 'Sortie stock',
                ]);

                $affectationId = $affectation->id;

            } elseif ($typeAffectationParcinfo === 'LICENCE') {
                $licenceId = $additionalData['licence_id'] ?? null;
                if (! $licenceId) {
                    throw new \Exception("L'identifiant de la licence est requis pour une affectation LICENCE.");
                }

                $licence = Licence::findOrFail($licenceId);
                $licence->actif = true;
                $licence->save();

                // Créer l'AffectationLicence
                $affectation = AffectationLicence::create([
                    'licence_id' => $licenceId,
                    'employe_id' => ($typeCible === 'EMPLOYE') ? $cibleId : null,
                    'equipement_id' => ($typeCible === 'EQUIPEMENT') ? $cibleId : null,
                    'type_affectation' => 'PERMANENTE',
                    'date_affectation' => now(),
                    'actif' => true,
                    'notes' => $additionalData['motif'] ?? 'Affectation licence depuis stock',
                ]);

                $affectationId = $affectation->id;
            }

            // 3. Enregistrer l'affectation du mouvement
            DB::table('stock_mouvements_affectations')->insert([
                'mouvement_id' => $mouvement->id,
                'type_affectation_parcinfo' => $typeAffectationParcinfo,
                'affectation_id' => $affectationId ?? 0, // Si prestation ou autre
                'type_cible' => $typeCible,
                'cible_id' => $cibleId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            activity()
                ->performedOn($mouvement)
                ->withProperties([
                    'type_affectation_parcinfo' => $typeAffectationParcinfo,
                    'affectation_id' => $affectationId,
                ])
                ->log('stock_exit_created');

            return $mouvement;
        });
    }
}
