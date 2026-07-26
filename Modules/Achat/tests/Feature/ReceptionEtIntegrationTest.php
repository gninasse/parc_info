<?php

namespace Modules\Achat\Tests\Feature;

use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\WizardData;
use Modules\Achat\Services\BordereauLivraisonService;
use Modules\Achat\Services\WizardValidationService;
use Modules\ParcInfo\Models\Equipement;

/**
 * Réception et intégration au parc.
 * Couvre RGC-02 à RGC-06, RGC-09 et les scénarios REC-07 à REC-15.
 */
class ReceptionEtIntegrationTest extends AchatTestCase
{
    /** RG-BL-01 — Une livraison exige un bon validé. */
    public function test_aucune_reception_sur_un_bon_en_brouillon(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommande($article, 3);

        $this->expectException(RegleMetierException::class);

        $this->creerBordereau($bonCommande, $article, 1);
    }

    /** REC-07 — Réception partielle : le reste à livrer est tenu à jour. */
    public function test_une_reception_partielle_met_a_jour_le_reste_a_livrer(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 10);
        $bordereau = $this->creerBordereau($bonCommande, $article, 6);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $bonCommande->refresh()->load('lignesCommande');

        $this->assertSame('partiel', $bonCommande->statut);
        $this->assertSame(6, $bonCommande->lignesCommande->first()->quantite_livree);
        $this->assertSame(4, $bonCommande->lignesCommande->first()->reste_a_livrer);
    }

    /** RGC-02 / REC-08 — La quantité reçue ne peut dépasser le reste à livrer. */
    public function test_une_quantite_superieure_au_reste_a_livrer_est_refusee(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 3);

        $this->expectException(RegleMetierException::class);

        $this->creerBordereau($bonCommande, $article, 5);
    }

    /** RGC-09 — La référence du bordereau fournisseur est unique. */
    public function test_la_reference_du_bordereau_physique_est_unique(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 6);

        app(BordereauLivraisonService::class)->creer(
            [
                'bon_de_commande_id' => $bonCommande->id,
                'date_livraison' => now()->toDateString(),
                'ref_bordereau_physique' => 'DOUBLON-001',
            ],
            [['article_id' => $article->id, 'quantite_livree' => 2]]
        );

        $this->expectException(\Illuminate\Database\QueryException::class);

        app(BordereauLivraisonService::class)->creer(
            [
                'bon_de_commande_id' => $bonCommande->id,
                'date_livraison' => now()->toDateString(),
                'ref_bordereau_physique' => 'DOUBLON-001',
            ],
            [['article_id' => $article->id, 'quantite_livree' => 1]]
        );
    }

    /** REC-13 — La dernière livraison solde la commande. */
    public function test_la_commande_passe_a_livre_lorsque_tout_est_recu(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 10);

        $premier = $this->creerBordereau($bonCommande, $article, 6);
        app(WizardValidationService::class)->validerBordereau($premier, $this->utilisateur->id);

        $second = $this->creerBordereau($bonCommande->refresh(), $article, 4);
        app(WizardValidationService::class)->validerBordereau($second, $this->utilisateur->id);

        $this->assertSame('livre', $bonCommande->refresh()->statut);
    }

    /** REC-09 — L'intégration crée une fiche équipement par unité. */
    public function test_l_integration_cree_une_fiche_equipement_par_unite(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 3);
        $bordereau = $this->creerBordereau($bonCommande, $article, 3);

        $this->saisirInventaire($bordereau, $article, 3);

        $resultat = app(WizardValidationService::class)
            ->validerBordereau($bordereau, $this->utilisateur->id);

        $this->assertCount(3, $resultat['equipements']);
        $this->assertSame(3, Equipement::where('ref_bordereau', $bordereau->numero_livraison)->count());

        $equipement = Equipement::where('ref_bordereau', $bordereau->numero_livraison)->first();

        // RGC-06 : la valeur d'acquisition est le prix commandé.
        $this->assertEqualsWithDelta(100000, (float) $equipement->valeur_achat, 0.01);
        $this->assertSame('en_stock', $equipement->statut);
        $this->assertSame('bon', $equipement->etat);
    }

    /** RG-INT-04 — Le code inventaire est généré s'il n'est pas saisi. */
    public function test_le_code_inventaire_est_genere_et_unique(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 3);
        $bordereau = $this->creerBordereau($bonCommande, $article, 3);

        $this->saisirInventaire($bordereau, $article, 3);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $codes = Equipement::where('ref_bordereau', $bordereau->numero_livraison)
            ->pluck('code_inventaire');

        $this->assertCount(3, $codes->unique());
        $codes->each(fn ($code) => $this->assertMatchesRegularExpression('/^INV-\d{4}-\d{4}$/', $code));
    }

    /** RGC-04 / REC-11 — Un numéro de série déjà présent bloque l'intégration. */
    public function test_un_numero_de_serie_en_doublon_bloque_l_integration(): void
    {
        $article = $this->creerArticle();

        $premierBon = $this->creerBonCommandeValide($article, 1);
        $premierBl = $this->creerBordereau($premierBon, $article, 1);
        $this->saisirInventaire($premierBl, $article, 1, 'IDENTIQUE');
        app(WizardValidationService::class)->validerBordereau($premierBl, $this->utilisateur->id);

        // Un second bordereau réutilise le même numéro de série.
        $secondBon = $this->creerBonCommandeValide($article, 1);
        $secondBl = $this->creerBordereau($secondBon, $article, 1);

        app(WizardValidationService::class)->sauvegarderEtape(
            $secondBl,
            $article,
            [['numero_serie' => "IDENTIQUE-{$premierBl->id}-1", 'code_inventaire' => '', 'champs_valeurs' => []]],
            null,
            true
        );

        $equipementsAvant = Equipement::count();

        try {
            app(WizardValidationService::class)->validerBordereau($secondBl, $this->utilisateur->id);
            $this->fail('Un numéro de série en doublon aurait dû être refusé.');
        } catch (RegleMetierException $e) {
            $this->assertStringContainsString('existe déjà dans le parc', $e->getMessage());
        }

        // RGC-05 / REC-12 : aucune création partielle, le bordereau reste ouvert.
        $this->assertSame($equipementsAvant, Equipement::count());
        $this->assertNotSame('valide', $secondBl->refresh()->statut);
    }

    /** RGC-05 / REC-12 — L'intégration est atomique. */
    public function test_une_erreur_en_cours_d_integration_n_laisse_aucune_trace(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 3);
        $bordereau = $this->creerBordereau($bonCommande, $article, 3);

        // La troisième unité est privée de numéro de série : l'intégration
        // échouera après la création des deux premières.
        app(WizardValidationService::class)->sauvegarderEtape($bordereau, $article, [
            ['numero_serie' => 'SN-OK-1', 'code_inventaire' => '', 'champs_valeurs' => []],
            ['numero_serie' => 'SN-OK-2', 'code_inventaire' => '', 'champs_valeurs' => []],
            ['numero_serie' => '', 'code_inventaire' => '', 'champs_valeurs' => []],
        ], null, true);

        try {
            app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);
            $this->fail('Une unité sans numéro de série aurait dû être refusée.');
        } catch (RegleMetierException) {
            // Attendu.
        }

        $this->assertSame(0, Equipement::count(), 'Aucun équipement ne doit subsister.');
        $this->assertSame(0, $bonCommande->refresh()->lignesCommande->sum('quantite_livree'));
        $this->assertSame('valide', $bonCommande->statut);
        // Le bordereau n'a pas été validé : l'intégration reste à reprendre.
        $this->assertNotSame('valide', $bordereau->refresh()->statut);
    }

    /** RG-WZ-06 — Le nombre d'unités doit correspondre à la quantité livrée. */
    public function test_une_saisie_incomplete_bloque_la_validation(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 3);
        $bordereau = $this->creerBordereau($bonCommande, $article, 3);

        $this->saisirInventaire($bordereau, $article, 2); // 2 unités pour 3 livrées

        $this->expectException(RegleMetierException::class);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);
    }

    /** REC-15 — Une livraison de consommables se valide sans assistant. */
    public function test_une_livraison_de_consommables_est_integree_sans_saisie(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 20);
        $bordereau = $this->creerBordereau($bonCommande, $article, 20);

        $resultat = app(WizardValidationService::class)
            ->validerBordereau($bordereau, $this->utilisateur->id);

        $this->assertEmpty($resultat['equipements']);
        $this->assertSame('valide', $bordereau->refresh()->statut);
        // La projection portée par l'article est mise à jour une seule fois.
        $this->assertSame(20, $article->refresh()->stock_actuel);
    }

    /** RG-WZ-08 — Les saisies temporaires sont purgées. */
    public function test_les_donnees_de_l_assistant_sont_purgees_apres_validation(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);

        $this->saisirInventaire($bordereau, $article, 2);
        $this->assertSame(1, WizardData::where('bordereau_livraison_id', $bordereau->id)->count());

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $this->assertSame(0, WizardData::where('bordereau_livraison_id', $bordereau->id)->count());
    }

    /** RGC-03 — Un bordereau validé ne peut pas être revalidé. */
    public function test_un_bordereau_valide_ne_peut_pas_etre_revalide(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 5);
        $bordereau = $this->creerBordereau($bonCommande, $article, 5);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $this->expectException(RegleMetierException::class);

        app(WizardValidationService::class)->validerBordereau($bordereau->refresh(), $this->utilisateur->id);
    }

    /** EF-BL-12 / REC-20 — Retour au brouillon depuis l'assistant. */
    public function test_un_bordereau_en_cours_d_integration_peut_revenir_en_brouillon(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);

        $this->get(route('achat.bordereaux.wizard', $bordereau))->assertOk();
        $this->assertSame('wizard', $bordereau->refresh()->statut);

        app(BordereauLivraisonService::class)->revenirEnBrouillon($bordereau);

        $bordereau->refresh();
        $this->assertSame('brouillon', $bordereau->statut);
        $this->assertTrue($bordereau->estModifiable());
    }

    public function test_un_bordereau_valide_ne_peut_pas_revenir_en_brouillon(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);

        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $this->expectException(RegleMetierException::class);

        app(BordereauLivraisonService::class)->revenirEnBrouillon($bordereau->refresh());
    }

    /** ENF-TRA-04 — La chaîne commande → livraison → équipement est reconstituable. */
    public function test_un_equipement_reste_rattachable_a_sa_commande(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 1);
        $bordereau = $this->creerBordereau($bonCommande, $article, 1);

        $this->saisirInventaire($bordereau, $article, 1);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $equipement = Equipement::first();

        $this->assertSame($bordereau->numero_livraison, $equipement->ref_bordereau);

        $bordereauRetrouve = \Modules\Achat\Models\BordereauLivraison::where(
            'numero_livraison',
            $equipement->ref_bordereau
        )->first();

        $this->assertSame($bonCommande->id, $bordereauRetrouve->bon_de_commande_id);
        $this->assertSame($this->fournisseur->id, $bordereauRetrouve->bonCommande->fournisseur_id);
    }
}
