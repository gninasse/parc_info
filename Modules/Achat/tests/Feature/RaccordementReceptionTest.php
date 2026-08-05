<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Exceptions\AchatReceptionException;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\AchatReceptionService;
use Modules\Catalogue\Models\Article;
use Tests\TestCase;

/**
 * Raccordement Achat ⇄ Stock — service d'intégration des réceptions
 * (`RACCORDEMENT_Achat_Stock.md` §3 · API_Inter_Modules §5.1/5.2).
 *
 * Ce service est le point où le stock physique et le reste à livrer pourraient
 * diverger. Les tests couvrent la recette du raccordement (§7) : plafonds,
 * concurrence, idempotence, contre-passation, statuts recalculés.
 */
class RaccordementReceptionTest extends TestCase
{
    use RefreshDatabase;

    private AchatReceptionService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(AchatReceptionService::class);
    }

    /**
     * Un bon validé de 10 unités d'un article, prêt à recevoir.
     *
     * @return array{0: BonCommande, 1: LigneCommande, 2: Article}
     */
    private function bonAvecUneLigne(float $quantite = 10, float $dejaLivree = 0): array
    {
        $article = Article::factory()->create(['nom' => 'Latitude 3540']);
        $bon = BonCommande::factory()->valide()->create();

        $ligne = LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'designation' => 'Latitude 3540',
            'quantite' => $quantite,
            'quantite_livree' => $dejaLivree,
            'prix_unitaire_ht' => 830000,
            'taux_tva' => 18,
        ]);

        return [$bon, $ligne, $article];
    }

    // ── Nominal ────────────────────────────────────────────────────────────

    public function test_une_livraison_partielle_incremente_et_passe_le_bon_en_partiel(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $resultat = $this->service->integrer(
            $bon->id,
            entreeId: 34,
            lignesRecues: [['article_id' => $article->id, 'quantite' => 6]],
            reference: 'ENT-2026-0034'
        );

        $this->assertSame(BonCommande::STATUT_PARTIEL, $resultat->statutBc);
        $this->assertEqualsWithDelta(6, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertEqualsWithDelta(4, $ligne->fresh()->reste, 0.001);
        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->fresh()->statut);
    }

    public function test_une_livraison_complete_solde_le_bon(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $resultat = $this->service->integrer(
            $bon->id,
            entreeId: 35,
            lignesRecues: [['article_id' => $article->id, 'quantite' => 10]]
        );

        $this->assertSame(BonCommande::STATUT_LIVRE, $resultat->statutBc);
        $this->assertTrue($ligne->fresh()->estSoldee());
    }

    public function test_deux_livraisons_successives_soldent_le_bon(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 40, [['article_id' => $article->id, 'quantite' => 6]]);
        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->fresh()->statut);

        $resultat = $this->service->integrer($bon->id, 41, [['article_id' => $article->id, 'quantite' => 4]]);

        $this->assertSame(BonCommande::STATUT_LIVRE, $resultat->statutBc);
        $this->assertEqualsWithDelta(10, (float) $ligne->fresh()->quantite_livree, 0.001);
    }

    /** Le résumé est la phrase qui atterrit dans la chronologie. */
    public function test_le_resultat_porte_une_phrase_de_chronologie(): void
    {
        [$bon, , $article] = $this->bonAvecUneLigne(10);

        $resultat = $this->service->integrer(
            $bon->id, 42, [['article_id' => $article->id, 'quantite' => 6]], 'ENT-2026-0034'
        );

        $this->assertStringContainsString('ENT-2026-0034', $resultat->resume('ENT-2026-0034'));
        $this->assertStringContainsString($bon->numero, $resultat->resume('ENT-2026-0034'));
    }

    // ── IA-4 : le plafond, revérifié au moment de l'intégration ────────────

    public function test_livrer_plus_que_le_reste_est_refuse(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        try {
            $this->service->integrer($bon->id, 50, [['article_id' => $article->id, 'quantite' => 11]]);
            $this->fail('Le dépassement du reste à livrer aurait dû être refusé.');
        } catch (AchatReceptionException $e) {
            $this->assertSame(422, $e->status());
            $this->assertArrayHasKey((string) $ligne->id, $e->erreursParLigne());
            // Le message nomme la ligne, le reste, et propose une sortie
            $this->assertStringContainsString('Latitude 3540', $e->getMessage());
            $this->assertStringContainsString('reste à livrer 10', $e->getMessage());
            $this->assertStringContainsString('complémentaire', $e->getMessage());
        }

        // Rien n'a été écrit
        $this->assertEqualsWithDelta(0, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);
    }

    /**
     * Deux bons d'entrée concurrents sur le même reste : le second échoue.
     * C'est le scénario 3 de la recette du raccordement (§7).
     */
    public function test_deux_receptions_concurrentes_ne_depassent_pas_le_reste(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        // Le premier bon d'entrée prend 6 des 10 unités
        $this->service->integrer($bon->id, 60, [['article_id' => $article->id, 'quantite' => 6]]);

        // Le second, préparé quand le reste était encore de 10, en annonce 8
        $this->expectException(AchatReceptionException::class);

        $this->service->integrer($bon->id, 61, [['article_id' => $article->id, 'quantite' => 8]]);
    }

    /** Toutes les lignes fautives sont signalées d'un coup, pas la première. */
    public function test_les_depassements_sont_signales_ligne_par_ligne(): void
    {
        $bon = BonCommande::factory()->valide()->create();
        $premier = Article::factory()->create();
        $second = Article::factory()->create();

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'article_id' => $premier->id,
            'designation' => 'Article A', 'quantite' => 5,
        ]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'article_id' => $second->id,
            'designation' => 'Article B', 'quantite' => 3,
        ]);

        try {
            $this->service->integrer($bon->id, 70, [
                ['article_id' => $premier->id, 'quantite' => 6],
                ['article_id' => $second->id, 'quantite' => 4],
            ]);
            $this->fail('Les deux dépassements auraient dû être refusés.');
        } catch (AchatReceptionException $e) {
            $this->assertCount(2, $e->erreursParLigne());
            $this->assertStringContainsString('2 lignes dépassent', $e->getMessage());
        }
    }

    /** Deux lignes du même article se cumulent face au reste. */
    public function test_le_plafond_s_applique_au_cumul_du_meme_article(): void
    {
        [$bon, , $article] = $this->bonAvecUneLigne(10);

        $this->expectException(AchatReceptionException::class);

        $this->service->integrer($bon->id, 80, [
            ['article_id' => $article->id, 'quantite' => 6],
            ['article_id' => $article->id, 'quantite' => 5], // cumul = 11 > 10
        ]);
    }

    // ── IA-5 : idempotence ─────────────────────────────────────────────────

    public function test_rejouer_une_integration_n_incremente_pas_deux_fois(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $premier = $this->service->integrer($bon->id, 90, [['article_id' => $article->id, 'quantite' => 6]]);
        $rejeu = $this->service->integrer($bon->id, 90, [['article_id' => $article->id, 'quantite' => 6]]);

        $this->assertFalse($premier->dejaIntegre);
        $this->assertTrue($rejeu->dejaIntegre);

        $this->assertEqualsWithDelta(6, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(1, IntegrationReception::query()->receptions()->count());
    }

    /** Deux bons d'entrée distincts s'intègrent bien tous les deux. */
    public function test_l_idempotence_ne_bloque_pas_deux_entrees_distinctes(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 100, [['article_id' => $article->id, 'quantite' => 3]]);
        $this->service->integrer($bon->id, 101, [['article_id' => $article->id, 'quantite' => 3]]);

        $this->assertEqualsWithDelta(6, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(2, IntegrationReception::query()->receptions()->count());
    }

    // ── Statuts et gardes ──────────────────────────────────────────────────

    public function test_un_bon_non_livrable_est_refuse(): void
    {
        $bon = BonCommande::factory()->create(); // brouillon
        $article = Article::factory()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id, 'article_id' => $article->id, 'quantite' => 5]);

        $this->expectException(AchatReceptionException::class);
        $this->expectExceptionMessage("n'est pas livrable");

        $this->service->integrer($bon->id, 110, [['article_id' => $article->id, 'quantite' => 1]]);
    }

    /** Un bon clôturé a vu son reliquat abandonné : il ne se rouvre pas. */
    public function test_un_bon_cloture_n_accepte_plus_de_reception(): void
    {
        $bon = BonCommande::factory()->cloture()->create();
        $article = Article::factory()->create();
        LigneCommande::factory()->create(['bon_commande_id' => $bon->id, 'article_id' => $article->id, 'quantite' => 5]);

        $this->expectException(AchatReceptionException::class);

        $this->service->integrer($bon->id, 120, [['article_id' => $article->id, 'quantite' => 1]]);
    }

    /**
     * Régression : élargir les statuts recalculables à LIVRE (pour permettre
     * la contre-passation) ne doit pas ouvrir la porte à une réception
     * supplémentaire sur un bon déjà soldé.
     */
    public function test_un_bon_deja_solde_n_accepte_plus_de_reception(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 125, [['article_id' => $article->id, 'quantite' => 10]]);
        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->fresh()->statut);

        try {
            $this->service->integrer($bon->id, 126, [['article_id' => $article->id, 'quantite' => 1]]);
            $this->fail('Un bon soldé ne doit plus rien accepter.');
        } catch (AchatReceptionException $e) {
            // Le reste est nul : le plafond suffit à refuser
            $this->assertNotEmpty($e->getMessage());
        }

        $this->assertEqualsWithDelta(10, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->fresh()->statut);
    }

    /** Pas de mélange commande / hors commande sur un même bon (§2.3). */
    public function test_un_article_hors_commande_est_refuse(): void
    {
        [$bon] = $this->bonAvecUneLigne(10);
        $intrus = Article::factory()->create();

        $this->expectException(AchatReceptionException::class);
        $this->expectExceptionMessage("n'est pas sur la commande");

        $this->service->integrer($bon->id, 130, [['article_id' => $intrus->id, 'quantite' => 1]]);
    }

    // ── §5.2 : contre-passation ────────────────────────────────────────────

    public function test_une_contre_passation_decremente_et_ramene_le_bon_en_partiel(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 140, [['article_id' => $article->id, 'quantite' => 10]]);
        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->fresh()->statut);

        $resultat = $this->service->contrePasser(
            $bon->id,
            mouvementId: 900,
            lignesAnnulees: [['article_id' => $article->id, 'quantite' => 4]]
        );

        // Un bon LIVRE redevient PARTIEL : c'est explicitement attendu (§7.3)
        $this->assertSame(BonCommande::STATUT_PARTIEL, $resultat->statutBc);
        $this->assertEqualsWithDelta(6, (float) $ligne->fresh()->quantite_livree, 0.001);
    }

    /** Le décrément ne peut jamais rendre la quantité livrée négative. */
    public function test_la_contre_passation_a_un_plancher_a_zero(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 150, [['article_id' => $article->id, 'quantite' => 3]]);

        $this->service->contrePasser($bon->id, 901, [['article_id' => $article->id, 'quantite' => 99]]);

        $this->assertEqualsWithDelta(0, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(BonCommande::STATUT_VALIDE, $bon->fresh()->statut);
    }

    public function test_la_contre_passation_est_idempotente(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer($bon->id, 160, [['article_id' => $article->id, 'quantite' => 8]]);

        $this->service->contrePasser($bon->id, 902, [['article_id' => $article->id, 'quantite' => 3]]);
        $rejeu = $this->service->contrePasser($bon->id, 902, [['article_id' => $article->id, 'quantite' => 3]]);

        $this->assertTrue($rejeu->dejaIntegre);
        $this->assertEqualsWithDelta(5, (float) $ligne->fresh()->quantite_livree, 0.001);
        $this->assertSame(1, IntegrationReception::query()->contrePassations()->count());
    }

    // ── Trace d'audit ──────────────────────────────────────────────────────

    public function test_chaque_integration_laisse_une_trace_detaillee(): void
    {
        [$bon, $ligne, $article] = $this->bonAvecUneLigne(10);

        $this->service->integrer(
            $bon->id, 170, [['article_id' => $article->id, 'quantite' => 6]], 'ENT-2026-0034'
        );

        $trace = IntegrationReception::query()->receptions()->first();

        $this->assertSame($bon->id, $trace->bon_commande_id);
        $this->assertSame(170, $trace->entree_id);
        $this->assertSame('ENT-2026-0034', $trace->reference);
        $this->assertSame($ligne->id, $trace->detail[0]['ligne_id']);
        $this->assertEqualsWithDelta(6, $trace->detail[0]['quantite'], 0.001);
        $this->assertEqualsWithDelta(4, $trace->detail[0]['reste'], 0.001);
    }
}
