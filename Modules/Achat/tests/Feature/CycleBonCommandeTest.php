<?php

namespace Modules\Achat\Tests\Feature;

use Modules\Achat\Exceptions\RegleMetierException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Services\BonCommandeService;

/**
 * Cycle de vie du bon de commande.
 * Couvre RGC-01, RGC-07, RGC-10 et les scénarios REC-03, REC-05, REC-06, REC-19.
 */
class CycleBonCommandeTest extends AchatTestCase
{
    public function test_un_bon_de_commande_est_cree_en_brouillon_avec_un_numero_sequentiel(): void
    {
        $bonCommande = $this->creerBonCommande($this->creerArticle());

        $this->assertSame('brouillon', $bonCommande->statut);
        $this->assertMatchesRegularExpression('/^BC-\d{4}-0001$/', $bonCommande->numero_commande);
    }

    public function test_la_numerotation_ne_produit_jamais_de_doublon(): void
    {
        $article = $this->creerArticle();

        $numeros = collect(range(1, 5))
            ->map(fn () => $this->creerBonCommande($article)->numero_commande);

        $this->assertCount(5, $numeros->unique(), 'Les numéros doivent tous être distincts.');
    }

    /** REC-03 — Les montants appliquent le taux de TVA propre à chaque article. */
    public function test_les_montants_appliquent_le_taux_de_tva_de_chaque_article(): void
    {
        $articleTaxe = $this->creerArticle(['taux_tva' => 18]);
        $articleExonere = $this->creerArticle(['taux_tva' => 0]);

        $bonCommande = app(BonCommandeService::class)->creer(
            ['fournisseur_id' => $this->fournisseur->id, 'date_commande' => now()->toDateString()],
            [
                ['article_id' => $articleTaxe->id, 'quantite' => 2, 'prix_unitaire' => 100000],
                ['article_id' => $articleExonere->id, 'quantite' => 1, 'prix_unitaire' => 50000],
            ]
        );

        // 200 000 + 50 000 = 250 000 HT ; TVA = 18 % sur 200 000 uniquement.
        $this->assertEqualsWithDelta(250000, (float) $bonCommande->montant_ht, 0.01);
        $this->assertEqualsWithDelta(36000, (float) $bonCommande->montant_tva, 0.01);
        $this->assertEqualsWithDelta(286000, (float) $bonCommande->montant_ttc, 0.01);
    }

    /** RG-BC-05 — Le taux est figé sur la ligne à la commande. */
    public function test_une_evolution_du_taux_de_tva_ne_modifie_pas_un_bon_existant(): void
    {
        $article = $this->creerArticle(['taux_tva' => 18]);
        $bonCommande = $this->creerBonCommande($article, 1);

        $article->update(['taux_tva' => 5]);
        app(BonCommandeService::class)->recalculerMontants($bonCommande->refresh());

        $this->assertEqualsWithDelta(18000, (float) $bonCommande->refresh()->montant_tva, 0.01);
    }

    public function test_un_meme_article_ne_peut_pas_figurer_deux_fois(): void
    {
        $article = $this->creerArticle();

        $this->expectException(RegleMetierException::class);

        app(BonCommandeService::class)->creer(
            ['fournisseur_id' => $this->fournisseur->id, 'date_commande' => now()->toDateString()],
            [
                ['article_id' => $article->id, 'quantite' => 1, 'prix_unitaire' => 1000],
                ['article_id' => $article->id, 'quantite' => 2, 'prix_unitaire' => 1000],
            ]
        );
    }

    /** REC-05 — La validation verrouille le bon et trace son auteur. */
    public function test_la_validation_verrouille_le_bon_et_enregistre_son_auteur(): void
    {
        $bonCommande = $this->creerBonCommande($this->creerArticle());

        app(BonCommandeService::class)->valider($bonCommande, $this->utilisateur->id);
        $bonCommande->refresh();

        $this->assertSame('valide', $bonCommande->statut);
        $this->assertSame($this->utilisateur->id, $bonCommande->valide_par);
        $this->assertNotNull($bonCommande->date_validation);
        $this->assertFalse($bonCommande->estModifiable());
    }

    public function test_un_bon_sans_ligne_ne_peut_pas_etre_valide(): void
    {
        $bonCommande = BonCommande::create([
            'numero_commande' => 'BC-TEST-0001',
            'fournisseur_id' => $this->fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
        ]);

        $this->expectException(RegleMetierException::class);

        app(BonCommandeService::class)->valider($bonCommande, $this->utilisateur->id);
    }

    /** RGC-01 / REC-06 — Un bon validé n'est plus modifiable. */
    public function test_un_bon_valide_ne_peut_plus_etre_modifie(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article);

        $this->expectException(RegleMetierException::class);

        app(BonCommandeService::class)->modifier(
            $bonCommande,
            ['fournisseur_id' => $this->fournisseur->id, 'date_commande' => now()->toDateString()],
            [['article_id' => $article->id, 'quantite' => 9, 'prix_unitaire' => 1000]]
        );
    }

    /** REC-19 — La suppression reste possible sur un brouillon. */
    public function test_un_brouillon_peut_etre_supprime(): void
    {
        $bonCommande = $this->creerBonCommande($this->creerArticle());

        app(BonCommandeService::class)->supprimer($bonCommande);

        $this->assertSoftDeleted('achat_bons_commande', ['id' => $bonCommande->id]);
    }

    public function test_un_bon_valide_ne_peut_pas_etre_supprime(): void
    {
        $bonCommande = $this->creerBonCommandeValide($this->creerArticle());

        $this->expectException(RegleMetierException::class);

        app(BonCommandeService::class)->supprimer($bonCommande);
    }

    public function test_l_annulation_exige_un_motif_et_le_conserve(): void
    {
        $bonCommande = $this->creerBonCommandeValide($this->creerArticle());

        app(BonCommandeService::class)->annuler(
            $bonCommande,
            $this->utilisateur->id,
            'Fournisseur défaillant'
        );

        $bonCommande->refresh();

        $this->assertSame('annule', $bonCommande->statut);
        $this->assertSame('Fournisseur défaillant', $bonCommande->motif_annulation);
        $this->assertSame($this->utilisateur->id, $bonCommande->annule_par);
    }

    /** RGC-10 — Une commande déjà livrée ne peut plus être annulée. */
    public function test_un_bon_deja_livre_ne_peut_plus_etre_annule(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 4);
        $bordereau = $this->creerBordereau($bonCommande, $article, 4);

        app(\Modules\Achat\Services\WizardValidationService::class)
            ->validerBordereau($bordereau, $this->utilisateur->id);

        $this->assertFalse($bonCommande->refresh()->estAnnulable());

        $this->expectException(RegleMetierException::class);

        app(BonCommandeService::class)->annuler($bonCommande, $this->utilisateur->id, 'Trop tard');
    }

    /** EF-BC-18 — Clôture du reliquat d'une commande abandonnée. */
    public function test_le_reliquat_d_une_commande_partielle_peut_etre_cloture(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 10);
        $bordereau = $this->creerBordereau($bonCommande, $article, 4);

        app(\Modules\Achat\Services\WizardValidationService::class)
            ->validerBordereau($bordereau, $this->utilisateur->id);

        $bonCommande->refresh();
        $this->assertSame('partiel', $bonCommande->statut);

        app(BonCommandeService::class)->cloturerReliquat(
            $bonCommande,
            $this->utilisateur->id,
            'Marché soldé avec le fournisseur'
        );

        $bonCommande->refresh();

        $this->assertSame('cloture', $bonCommande->statut);
        $this->assertSame('Marché soldé avec le fournisseur', $bonCommande->motif_cloture);
        // La livraison déjà intégrée est préservée.
        $this->assertSame(4, $bonCommande->lignesCommande->sum('quantite_livree'));
    }

    public function test_l_ecran_de_liste_repond(): void
    {
        $this->get(route('achat.bons-commande.index'))->assertOk();
    }

    public function test_la_route_de_donnees_renvoie_la_structure_attendue(): void
    {
        $this->creerBonCommande($this->creerArticle());

        $this->getJson(route('achat.bons-commande.data'))
            ->assertOk()
            ->assertJsonStructure(['total', 'rows']);
    }
}
