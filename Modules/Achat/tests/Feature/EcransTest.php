<?php

namespace Modules\Achat\Tests\Feature;

use Modules\Achat\Services\WizardValidationService;

/**
 * Accessibilité de tous les écrans du module.
 *
 * Ce cas de test est un filet de sécurité : il ouvre chaque écran avec des
 * données réalistes, dans chacun des états que peut prendre un document.
 * Son absence avait laissé passer un appel invalide à la piste d'audit
 * (activity()->forSubject) sur les fiches détaillées, qui ne se manifestait
 * qu'à l'exécution.
 */
class EcransTest extends AchatTestCase
{
    public function test_le_tableau_de_bord_s_affiche(): void
    {
        $this->get(route('achat.dashboard.index'))->assertOk();
    }

    public function test_le_tableau_de_bord_s_affiche_avec_des_donnees(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 5);
        $bordereau = $this->creerBordereau($bonCommande, $article, 5);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        $this->get(route('achat.dashboard.index'))->assertOk();
    }

    public function test_les_ecrans_de_liste_s_affichent(): void
    {
        $this->creerArticle();

        $this->get(route('achat.articles.index'))->assertOk();
        $this->get(route('achat.bons-commande.index'))->assertOk();
        $this->get(route('achat.bordereaux.index'))->assertOk();
        $this->get(route('achat.stocks.index'))->assertOk();
        $this->get(route('achat.statistiques.index'))->assertOk();
    }

    public function test_les_formulaires_de_creation_s_affichent(): void
    {
        $this->creerArticle();

        $this->get(route('achat.bons-commande.create'))->assertOk();
        $this->get(route('achat.bordereaux.create'))->assertOk();
    }

    /** La fiche est ouverte dans chacun des statuts possibles du bon. */
    public function test_la_fiche_d_un_bon_de_commande_s_affiche_dans_tous_ses_etats(): void
    {
        $article = $this->creerConsommable();

        // Brouillon
        $brouillon = $this->creerBonCommande($article, 10);
        $this->get(route('achat.bons-commande.show', $brouillon))->assertOk();

        // Validé
        $valide = $this->creerBonCommandeValide($this->creerConsommable(), 10);
        $this->get(route('achat.bons-commande.show', $valide))->assertOk();

        // Partiellement livré
        $bordereau = $this->creerBordereau($valide, $valide->lignesCommande->first()->article, 4);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);
        $this->get(route('achat.bons-commande.show', $valide->refresh()))->assertOk();

        // Annulé, avec motif
        $annule = $this->creerBonCommandeValide($this->creerConsommable(), 2);
        app(\Modules\Achat\Services\BonCommandeService::class)
            ->annuler($annule, $this->utilisateur->id, 'Motif de test');
        $this->get(route('achat.bons-commande.show', $annule))->assertOk();
    }

    public function test_le_formulaire_de_modification_s_affiche_pour_un_brouillon(): void
    {
        $bonCommande = $this->creerBonCommande($this->creerArticle(), 3);

        $this->get(route('achat.bons-commande.edit', $bonCommande))->assertOk();
    }

    /** RG-BC-03 — La modification d'un bon validé redirige au lieu d'échouer. */
    public function test_le_formulaire_de_modification_redirige_pour_un_bon_valide(): void
    {
        $bonCommande = $this->creerBonCommandeValide($this->creerArticle(), 3);

        $this->get(route('achat.bons-commande.edit', $bonCommande))
            ->assertRedirect(route('achat.bons-commande.show', $bonCommande))
            ->assertSessionHas('error');
    }

    public function test_la_fiche_d_un_bordereau_s_affiche_dans_tous_ses_etats(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 6);

        // Brouillon
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);
        $this->get(route('achat.bordereaux.show', $bordereau))->assertOk();

        // En cours d'intégration
        $this->get(route('achat.bordereaux.wizard', $bordereau))->assertOk();
        $this->get(route('achat.bordereaux.show', $bordereau->refresh()))->assertOk();

        // Validé et intégré
        $this->saisirInventaire($bordereau, $article, 2);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);
        $this->get(route('achat.bordereaux.show', $bordereau->refresh()))->assertOk();
    }

    /** L'assistant est rendu avec les champs dynamiques de la catégorie. */
    public function test_l_assistant_s_affiche_pour_un_equipement(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);

        $this->get(route('achat.bordereaux.wizard', $bordereau))
            ->assertOk()
            ->assertSee($article->designation)
            ->assertSee('Unité n° 1');
    }

    /** RG-WZ-04 — Sans équipement ni licence, l'assistant valide directement. */
    public function test_l_assistant_valide_directement_une_livraison_de_consommables(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 8);
        $bordereau = $this->creerBordereau($bonCommande, $article, 8);

        $this->get(route('achat.bordereaux.wizard', $bordereau))
            ->assertRedirect(route('achat.bordereaux.show', $bordereau))
            ->assertSessionHas('success');

        $this->assertSame('valide', $bordereau->refresh()->statut);
    }

    public function test_les_documents_imprimables_sont_produits(): void
    {
        $article = $this->creerArticle();
        $bonCommande = $this->creerBonCommandeValide($article, 2);
        $bordereau = $this->creerBordereau($bonCommande, $article, 2);

        // Aperçu HTML
        $this->get(route('achat.bons-commande.imprimer', $bonCommande))->assertOk();

        // PDF
        $this->get(route('achat.bons-commande.imprimer', $bonCommande).'?pdf=1')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('achat.bordereaux.imprimer', $bordereau))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    /** Les quatre états standard répondent, à vide comme avec des données. */
    public function test_les_rapports_repondent(): void
    {
        $article = $this->creerConsommable();
        $bonCommande = $this->creerBonCommandeValide($article, 10);
        $bordereau = $this->creerBordereau($bonCommande, $article, 4);
        app(WizardValidationService::class)->validerBordereau($bordereau, $this->utilisateur->id);

        foreach (['global_purchases', 'by_supplier_detail', 'reliquats', 'popular_articles'] as $type) {
            $this->getJson(route('achat.statistiques.data', ['report_type' => $type]))
                ->assertOk()
                ->assertJsonStructure(['success', 'title', 'columns', 'rows']);

            $this->get(route('achat.statistiques.pdf', ['report_type' => $type]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }
    }

    /** Un type de rapport inconnu retombe sur le rapport par défaut. */
    public function test_un_type_de_rapport_inconnu_est_neutralise(): void
    {
        $this->getJson(route('achat.statistiques.data', ['report_type' => 'inexistant']))
            ->assertOk()
            ->assertJsonPath('title', 'Rapport global des bons de commande');
    }

    public function test_la_piste_d_audit_est_alimentee_et_lisible(): void
    {
        $bonCommande = $this->creerBonCommandeValide($this->creerArticle(), 2);

        $reponse = $this->get(route('achat.bons-commande.show', $bonCommande))->assertOk();

        $journal = $reponse->viewData('journal');

        $this->assertGreaterThanOrEqual(2, $journal->count(), 'Création et validation doivent être tracées.');
        $this->assertNotNull($journal->first()->description);
    }
}
