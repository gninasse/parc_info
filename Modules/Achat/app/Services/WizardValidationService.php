<?php

namespace Modules\Achat\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\WizardData;
use Modules\ParcInfo\Models\Consommable;
use Modules\ParcInfo\Models\Licence;
use Modules\ParcInfo\Models\Logiciel;
use Modules\ParcInfo\Models\MouvementConsommable;
use Modules\ParcInfo\Models\TypeConsommable;

class WizardValidationService
{
    public function __construct(
        protected EquipementIntegrationService $equipementService,
        protected CodeInventaireGeneratorService $codeGenerator
    ) {}

    /**
     * Sauvegarde temporairement les données du wizard pour un article.
     */
    public function sauvegarderEtape(BordereauLivraison $bl, Article $article, array $unitesData, ?array $attributsCommuns = null, bool $completed = false): WizardData
    {
        return WizardData::updateOrCreate(
            [
                'bordereau_livraison_id' => $bl->id,
                'article_id' => $article->id,
            ],
            [
                'unites_data' => $unitesData,
                'attributs_communs' => $attributsCommuns,
                'completed' => $completed,
            ]
        );
    }

    /**
     * Valide le bordereau de livraison et intègre les données dans le parc informatique.
     *
     * @throws Exception
     */
    public function validerBordereau(BordereauLivraison $bl, int $userId): array
    {
        if ($bl->statut === 'valide') {
            throw new Exception('Ce bordereau de livraison a déjà été validé.');
        }

        return DB::transaction(function () use ($bl, $userId) {
            $bl->load(['lignesLivraison.article', 'bonCommande.lignesCommande']);
            $createdEquipements = [];
            $createdLicences = [];

            // 1. Valider que toutes les données obligatoires du wizard sont complètes
            foreach ($bl->lignesLivraison as $line) {
                $article = $line->article;

                if (in_array($article->type_article, ['equipement', 'licence'])) {
                    $wizardData = WizardData::where('bordereau_livraison_id', $bl->id)
                        ->where('article_id', $article->id)
                        ->first();

                    if (! $wizardData || ! $wizardData->completed) {
                        throw new Exception("Les informations d'inventaire pour l'article '{$article->designation}' ne sont pas finalisées.");
                    }

                    if (count($wizardData->unites_data) !== (int) $line->quantite_livree) {
                        throw new Exception("La quantité saisie dans l'assistant pour '{$article->designation}' ne correspond pas à la quantité livrée.");
                    }
                }
            }

            // 2. Traiter chaque ligne de livraison
            foreach ($bl->lignesLivraison as $line) {
                $article = $line->article;

                // Récupérer le prix unitaire d'origine sur le bon de commande
                $ligneCommande = LigneCommande::where('bon_de_commande_id', $bl->bon_de_commande_id)
                    ->where('article_id', $article->id)
                    ->first();

                if (! $ligneCommande) {
                    throw new Exception("L'article '{$article->designation}' n'est pas présent dans le bon de commande associé.");
                }

                $prixUnitaire = $ligneCommande->prix_unitaire;

                // Charger les données du wizard
                $wizardData = WizardData::where('bordereau_livraison_id', $bl->id)
                    ->where('article_id', $article->id)
                    ->first();

                if ($article->type_article === 'equipement') {
                    // Création de fiches équipements physiques
                    foreach ($wizardData->unites_data as $unit) {
                        // S'assurer que le code inventaire est défini
                        $unit['code_inventaire'] = $unit['code_inventaire'] ?? $this->codeGenerator->generer();

                        $createdEquipements[] = $this->equipementService->creerEquipement(
                            $article,
                            $unit,
                            $bl,
                            $prixUnitaire,
                            $userId
                        );
                    }
                } elseif ($article->type_article === 'licence') {
                    // Création de licences logicielles
                    $logiciel = Logiciel::firstOrCreate(
                        ['nom' => $article->designation],
                        [
                            'code' => strtoupper(substr($article->code_article, 0, 50)),
                            'est_actif' => true,
                        ]
                    );

                    foreach ($wizardData->unites_data as $unit) {
                        $createdLicences[] = Licence::create([
                            'logiciel_id' => $logiciel->id,
                            'cle_licence' => $unit['cle_licence'] ?? 'N/A',
                            'date_acquisition' => $bl->date_livraison,
                            'date_activation' => $unit['date_activation'] ?? now()->toDateString(),
                            'date_expiration' => $unit['date_expiration'] ?? null,
                            'cout_unitaire' => $prixUnitaire,
                            'cout_total' => $prixUnitaire,
                            'fournisseur_id' => $bl->bonCommande->fournisseur_id,
                            'actif' => true,
                            'statut' => 'VALIDE',
                            'notes' => "Acquisition automatique via la validation du BL n° {$bl->numero_livraison}",
                        ]);
                    }
                } elseif ($article->type_article === 'consommable') {
                    // 1. Incrémenter le stock de l'article catalogue
                    $article->increment('stock_actuel', $line->quantite_livree);

                    // 2. Intégrer également dans parc_info_consommables
                    $consommable = Consommable::where('code', $article->code_article)->first();
                    if (! $consommable) {
                        $typeCons = TypeConsommable::firstOrCreate(
                            ['code' => 'GEN-CONS'],
                            [
                                'nom' => 'Consommables Divers',
                                'categorie' => 'Achat',
                                'unite_stock' => 'Unité',
                                'seul_reapprovisionnement' => 5,
                            ]
                        );

                        $consommable = Consommable::create([
                            'code' => $article->code_article,
                            'nom' => $article->designation,
                            'type_consommable_id' => $typeCons->id,
                            'marque_id' => $article->marque_id,
                            'modele_reference' => $article->reference_constructeur ?? '',
                            'fournisseur_principal_id' => $bl->bonCommande->fournisseur_id,
                            'cout_unitaire' => $prixUnitaire,
                            'quantite_stock_actuel' => 0,
                            'quantite_stock_min' => $article->seuil_alerte,
                            'est_actif' => true,
                        ]);
                    }

                    $consommable->increment('quantite_stock_actuel', $line->quantite_livree);
                    $consommable->update(['date_dernier_approvisionnement' => $bl->date_livraison]);

                    // 3. Ajouter un mouvement de stock dans parc_info_mouvements_consommables
                    MouvementConsommable::create([
                        'consommable_id' => $consommable->id,
                        'type_mouvement' => 'entree',
                        'quantite' => $line->quantite_livree,
                        'prix_unitaire' => $prixUnitaire,
                        'date_mouvement' => now(),
                        'reference_commande' => $bl->bonCommande->numero_commande,
                        'utilisateur_id' => $userId,
                        'raison' => "Entrée de stock automatique via validation du BL n° {$bl->numero_livraison}",
                    ]);
                }

                // Mettre à jour la quantité livrée sur le bon de commande
                $ligneCommande->increment('quantite_livree', $line->quantite_livree);
            }

            // 3. Mettre à jour le statut du Bon de Commande
            $bc = $bl->bonCommande;
            $bc->load('lignesCommande');

            $totalCommandee = $bc->lignesCommande->sum('quantite');
            $totalLivree = $bc->lignesCommande->sum('quantite_livree');

            if ($totalLivree >= $totalCommandee) {
                $bc->update(['statut' => 'livre']);
            } else {
                $bc->update(['statut' => 'partiel']);
            }

            // 4. Mettre à jour le statut du Bordereau de Livraison
            $bl->update(['statut' => 'valide']);

            // Supprimer les données temporaires du wizard
            WizardData::where('bordereau_livraison_id', $bl->id)->delete();

            return [
                'equipements' => $createdEquipements,
                'licences' => $createdLicences,
            ];
        });
    }
}
