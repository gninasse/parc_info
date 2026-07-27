<?php

namespace Modules\Achat\Services;

use Illuminate\Support\Facades\DB;
use Modules\Achat\Contracts\ParcInfoIntegrationInterface;
use Modules\Achat\Events\BordereauLivraisonValide;
use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BordereauLivraison;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Models\LigneLivraison;
use Modules\Achat\Models\WizardData;

/**
 * Assistant d'intégration : saisie d'inventaire puis création définitive des
 * enregistrements dans ParcInfo.
 *
 * RGC-05 / RG-INT-02 — L'intégration est atomique. Toute exception, à
 * n'importe quelle étape, annule l'ensemble : aucun équipement partiellement
 * créé, aucun compteur incrémenté, aucun statut modifié.
 */
class WizardValidationService
{
    public function __construct(
        protected ParcInfoIntegrationInterface $parcInfo,
        protected CodeInventaireGeneratorService $generateurCodeInventaire,
        protected BonCommandeService $bonCommandeService,
    ) {}

    /**
     * RG-WZ-07 — Enregistrement d'une étape, reprise possible ultérieurement.
     */
    public function sauvegarderEtape(
        BordereauLivraison $bordereau,
        Article $article,
        array $unites,
        ?array $attributsCommuns = null,
        bool $complete = false
    ): WizardData {
        if ($bordereau->estValide()) {
            throw new RegleMetierException('Ce bordereau est déjà validé : sa saisie ne peut plus être modifiée.');
        }

        return WizardData::updateOrCreate(
            [
                'bordereau_livraison_id' => $bordereau->id,
                'article_id' => $article->id,
            ],
            [
                'unites_data' => array_values($unites),
                'attributs_communs' => $attributsCommuns,
                'completed' => $complete,
            ]
        );
    }

    /**
     * Valide le bordereau et intègre son contenu au parc.
     *
     * @return array{equipements: array<int>, licences: array<int>}
     *
     * @throws RegleMetierException
     */
    public function validerBordereau(BordereauLivraison $bordereau, int $userId): array
    {
        // RG-INT-01
        if ($bordereau->estValide()) {
            throw new RegleMetierException('Ce bordereau de livraison a déjà été validé.');
        }

        $bordereau->load(['lignesLivraison.article', 'bonCommande.lignesCommande']);

        $this->controlerCompletude($bordereau);

        return DB::transaction(function () use ($bordereau, $userId) {
            $equipements = [];
            $licences = [];

            foreach ($bordereau->lignesLivraison as $ligne) {
                $article = $ligne->article;
                $ligneCommande = $this->ligneCommandeDe($bordereau, $article->id);

                match ($article->type_article) {
                    'equipement' => $equipements = array_merge(
                        $equipements,
                        $this->integrerEquipements($bordereau, $ligne, $ligneCommande, $userId)
                    ),
                    'licence' => $licences = array_merge(
                        $licences,
                        $this->integrerLicences($bordereau, $ligne, $ligneCommande, $userId)
                    ),
                    // consommable et prestation : aucun objet physique à créer
                    default => null,
                };

                // RG-INT-10
                $ligneCommande->increment('quantite_livree', $ligne->quantite_livree);
            }

            // RG-BC-11
            $this->bonCommandeService->actualiserStatutApresLivraison($bordereau->bonCommande);

            // RGC-03
            $bordereau->update([
                'statut' => 'valide',
                'valide_par' => $userId,
                'date_validation' => now(),
            ]);

            // RG-WZ-08
            $bordereau->wizardData()->delete();

            activity()->performedOn($bordereau)
                ->withProperties([
                    'equipements' => count($equipements),
                    'licences' => count($licences),
                ])
                ->log("Bordereau {$bordereau->numero_livraison} validé et intégré au parc");

            event(new BordereauLivraisonValide($bordereau, $userId, $equipements, $licences));

            return [
                'equipements' => $equipements,
                'licences' => $licences,
            ];
        });
    }

    /**
     * RG-WZ-05 / RG-WZ-06 — Contrôle préalable, hors transaction.
     *
     * @throws RegleMetierException
     */
    protected function controlerCompletude(BordereauLivraison $bordereau): void
    {
        if ($bordereau->lignesLivraison->isEmpty()) {
            throw new RegleMetierException('Ce bordereau ne comporte aucune ligne : il ne peut pas être validé.');
        }

        foreach ($bordereau->lignesLivraison as $ligne) {
            $article = $ligne->article;

            if (! $article->necessiteWizard()) {
                continue;
            }

            $saisie = $bordereau->wizardData
                ->firstWhere('article_id', $article->id)
                ?? WizardData::where('bordereau_livraison_id', $bordereau->id)
                    ->where('article_id', $article->id)
                    ->first();

            if (! $saisie || ! $saisie->completed) {
                throw new RegleMetierException(
                    "Les informations d'inventaire pour l'article « {$article->designation} » ne sont pas finalisées."
                );
            }

            if (count($saisie->unites_data ?? []) !== (int) $ligne->quantite_livree) {
                throw new RegleMetierException(
                    "La quantité saisie dans l'assistant pour « {$article->designation} » ne correspond pas ".
                    "à la quantité livrée ({$ligne->quantite_livree})."
                );
            }
        }
    }

    /**
     * RG-INT-03 à RG-INT-06 — Création des fiches équipement.
     *
     * @return array<int> Identifiants des équipements créés
     */
    protected function integrerEquipements(
        BordereauLivraison $bordereau,
        LigneLivraison $ligne,
        LigneCommande $ligneCommande,
        int $userId
    ): array {
        $article = $ligne->article;
        $saisie = $this->saisieDe($bordereau, $article->id);
        $attributsCommuns = $saisie->attributs_communs ?? [];
        $creees = [];

        foreach ($saisie->unites_data as $index => $unite) {
            $numeroSerie = mb_strtoupper(trim($unite['numero_serie'] ?? ''));

            if ($numeroSerie === '') {
                throw new RegleMetierException(
                    "Le numéro de série est obligatoire pour l'unité #".($index + 1).
                    " de l'article « {$article->designation} »."
                );
            }

            if ($this->parcInfo->existeNumeroSerie($numeroSerie)) {
                throw new RegleMetierException("Le numéro de série « {$numeroSerie} » existe déjà dans le parc.");
            }

            $codeInventaire = mb_strtoupper(trim($unite['code_inventaire'] ?? ''));

            if ($codeInventaire === '') {
                // RG-INT-04 : génération par séquence verrouillée
                $codeInventaire = $this->generateurCodeInventaire->generer();
            } elseif ($this->parcInfo->existeCodeInventaire($codeInventaire)) {
                throw new RegleMetierException("Le code inventaire « {$codeInventaire} » existe déjà dans le parc.");
            }

            // EF-INT-18 : les attributs communs sont appliqués puis surchargés
            // par la saisie propre à l'unité.
            $champsValeurs = array_merge($attributsCommuns, $unite['champs_valeurs'] ?? []);

            $equipementId = $this->parcInfo->creerEquipement([
                'categorie_id' => $article->categorie_equipement_id,
                'code_inventaire' => $codeInventaire,
                'numero_serie' => $numeroSerie,
                'marque_id' => $article->marque_id,
                'modele' => $article->designation,
                'date_acquisition' => $bordereau->date_livraison,
                // RG-INT-05 / RGC-06 : valeur d'acquisition = prix commandé
                'valeur_achat' => (float) $ligneCommande->prix_unitaire,
                'ref_bordereau' => $bordereau->numero_livraison,
                'champs_valeurs' => $champsValeurs,
            ]);

            $this->parcInfo->historiserAcquisition(
                $equipementId,
                $userId,
                "Acquisition via la validation du BL n° {$bordereau->numero_livraison} ".
                "(BC n° {$bordereau->bonCommande->numero_commande})",
                $bordereau->numero_livraison
            );

            $creees[] = $equipementId;
        }

        return $creees;
    }

    /**
     * RG-INT-07 — Création des licences logicielles.
     *
     * @return array<int> Identifiants des licences créées
     */
    protected function integrerLicences(
        BordereauLivraison $bordereau,
        LigneLivraison $ligne,
        LigneCommande $ligneCommande,
        int $userId
    ): array {
        $article = $ligne->article;
        $saisie = $this->saisieDe($bordereau, $article->id);

        $logicielId = $this->parcInfo->trouverOuCreerLogiciel(
            $article->designation,
            mb_strtoupper($article->code_article)
        );

        $creees = [];

        foreach ($saisie->unites_data as $index => $unite) {
            $cle = trim($unite['cle_licence'] ?? '');

            if ($cle === '') {
                throw new RegleMetierException(
                    'La clé de licence est obligatoire pour l\'unité #'.($index + 1).
                    " de l'article « {$article->designation} »."
                );
            }

            $creees[] = $this->parcInfo->creerLicence([
                'logiciel_id' => $logicielId,
                'cle_licence' => $cle,
                'date_acquisition' => $bordereau->date_livraison,
                'date_activation' => $unite['date_activation'] ?? now()->toDateString(),
                'date_expiration' => $unite['date_expiration'] ?: null,
                'cout_unitaire' => (float) $ligneCommande->prix_unitaire,
                'fournisseur_id' => $bordereau->bonCommande->fournisseur_id,
                'notes' => "Acquisition via la validation du BL n° {$bordereau->numero_livraison}",
            ]);
        }

        return $creees;
    }

    /** @throws RegleMetierException */
    protected function ligneCommandeDe(BordereauLivraison $bordereau, int $articleId): LigneCommande
    {
        $ligneCommande = LigneCommande::where('bon_de_commande_id', $bordereau->bon_de_commande_id)
            ->where('article_id', $articleId)
            ->first();

        if (! $ligneCommande) {
            throw new RegleMetierException(
                "Un article livré n'est pas présent dans le bon de commande associé."
            );
        }

        return $ligneCommande;
    }

    /** @throws RegleMetierException */
    protected function saisieDe(BordereauLivraison $bordereau, int $articleId): WizardData
    {
        $saisie = WizardData::where('bordereau_livraison_id', $bordereau->id)
            ->where('article_id', $articleId)
            ->first();

        if (! $saisie) {
            throw new RegleMetierException("Aucune saisie d'inventaire trouvée pour cet article.");
        }

        return $saisie;
    }
}
