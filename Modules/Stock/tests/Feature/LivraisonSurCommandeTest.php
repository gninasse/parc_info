<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\IntegrationReception;
use Modules\Achat\Models\LigneCommande;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Tests\TestCase;

/**
 * D-11/D-12 — le mode « Livraison sur commande » de bout en bout
 * (RACCORDEMENT §2 et §3).
 *
 * Les critères durs du recueil :
 *   - plafonds de SAISIE champ par champ (Request, message avec le reste) ;
 *   - fournisseur imposé par le BC (POST forgé refusé) ;
 *   - la VALIDATION Stock intègre la réception à Achat DANS la transaction :
 *     statut PARTIEL/LIVRE, quantite_livree incrémentée (IA-6) ;
 *   - le refus Achat (reste dépassé sous verrou) fait échouer TOUTE la
 *     validation : ni mouvement, ni niveau, ni statut (IA-4) ;
 *   - rejouer la même entrée n'incrémente rien (IA-5) ;
 *   - une entrée SANS commande fonctionne strictement comme avant.
 */
class LivraisonSurCommandeTest extends TestCase
{
    use RefreshDatabase;

    private User $magasinier;

    private Magasin $magasin;

    private Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatPermissionsSeeder::class);
        $this->seed(\Modules\Achat\Database\Seeders\AchatParametresSeeder::class);

        $this->magasinier = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->magasinier->assignRole('Magasinier');

        $this->magasin = Magasin::factory()->create();
        $this->fournisseur = Fournisseur::factory()->create();
    }

    /**
     * Un BC VALIDÉ de deux lignes consommables : 10 × toner à 100 000 et
     * 4 × câble à 5 000. Les articles sont stockables (ils reviennent dans
     * les lignes du bon d'entrée).
     *
     * @return array{0: BonCommande, 1: Article, 2: Article}
     */
    private function bonCommandeValide(): array
    {
        $toner = Article::factory()->consommable()->create(['nom' => 'Toner 26A']);
        $cable = Article::factory()->consommable()->create(['nom' => 'Câble HDMI']);

        $bon = BonCommande::factory()->valide()->create([
            'fournisseur_id' => $this->fournisseur->id,
        ]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $toner->id,
            'designation' => $toner->nom,
            'nature' => 'consommable',
            'quantite' => 10,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 100000,
        ]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $cable->id,
            'designation' => $cable->nom,
            'nature' => 'consommable',
            'quantite' => 4,
            'quantite_livree' => 0,
            'prix_unitaire_ht' => 5000,
        ]);

        return [$bon->refresh(), $toner, $cable];
    }

    private function chargeEntree(BonCommande $bon, array $lignes, array $surcharges = []): array
    {
        return array_merge([
            'magasin_id' => $this->magasin->id,
            'date_document' => now()->format('Y-m-d'),
            'nature' => 'livraison',
            'fournisseur_id' => $bon->fournisseur_id,
            'reference_externe' => 'BL-7788',
            'bon_commande_id' => $bon->id,
            'lignes' => $lignes,
        ], $surcharges);
    }

    /** Brouillon lié prêt à valider (lignes consommables, pas de tampon). */
    private function brouillonLie(BonCommande $bon, array $lignes): Entree
    {
        $entree = Entree::factory()->create([
            'magasin_id' => $this->magasin->id,
            'fournisseur_id' => $bon->fournisseur_id,
            'bon_commande_id' => $bon->id,
        ]);

        foreach ($lignes as $ligne) {
            LigneEntree::factory()->create(array_merge(['entree_id' => $entree->id], $ligne));
        }

        return $entree->refresh();
    }

    private function valider(Entree $entree, string $jeton = 'jeton-test')
    {
        return $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.valider', $entree->id), ['jeton' => $jeton]);
    }

    // ═══ La saisie : Request (plafonds, fournisseur, articles du BC) ════════

    public function test_un_brouillon_lie_valide_se_cree_normalement(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['article_id' => $toner->id, 'quantite' => 6, 'cout_unitaire' => 100000],
            ]))
            ->assertOk();

        $this->assertDatabaseHas('stock_entrees', [
            'bon_commande_id' => $bon->id,
            'reference_externe' => 'BL-7788',
        ]);
    }

    public function test_le_plafond_de_saisie_est_le_reste_a_livrer_message_compris(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();

        $reponse = $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['article_id' => $toner->id, 'quantite' => 11, 'cout_unitaire' => 100000],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lignes.0.quantite']);

        // Le message porte le RESTE (§15.2) : le magasinier sait de combien.
        $this->assertStringContainsString(
            'reste : 10',
            $reponse->json('errors')['lignes.0.quantite'][0]
        );
    }

    public function test_un_article_hors_commande_est_refuse_avec_la_sortie_metier(): void
    {
        [$bon] = $this->bonCommandeValide();
        $intrus = Article::factory()->consommable()->create(['nom' => 'Souris sans fil']);

        $reponse = $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['article_id' => $intrus->id, 'quantite' => 1, 'cout_unitaire' => 4000],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lignes.0.article_id']);

        $this->assertStringContainsString('n\'est pas sur la commande', $reponse->json('errors')['lignes.0.article_id'][0]);
    }

    public function test_le_fournisseur_est_impose_par_le_bc_meme_sur_un_post_forge(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();
        $autreFournisseur = Fournisseur::factory()->create();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['article_id' => $toner->id, 'quantite' => 2, 'cout_unitaire' => 100000],
            ], ['fournisseur_id' => $autreFournisseur->id]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['fournisseur_id']);
    }

    public function test_un_bon_non_livrable_ne_se_lie_pas(): void
    {
        $brouillonBc = BonCommande::factory()->create(['fournisseur_id' => $this->fournisseur->id]);
        $toner = Article::factory()->consommable()->create();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($brouillonBc, [
                ['article_id' => $toner->id, 'quantite' => 1, 'cout_unitaire' => 1000],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bon_commande_id']);
    }

    public function test_un_retour_ne_se_lie_pas_a_une_commande(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['article_id' => $toner->id, 'quantite' => 1, 'cout_unitaire' => 1000],
            ], ['nature' => 'retour', 'observation_type' => 'retour_service']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['bon_commande_id']);
    }

    public function test_un_rattachement_d_unite_est_refuse_sur_un_bon_lie(): void
    {
        [$bon] = $this->bonCommandeValide();
        $fiche = \Modules\Stock\Database\Factories\ParcInfoDeTest::equipement();

        $this->actingAs($this->magasinier)
            ->postJson(route('stock.entrees.store'), $this->chargeEntree($bon, [
                ['equipement_id' => $fiche->id, 'quantite' => 1],
            ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['lignes.0.equipement_id']);
    }

    // ═══ La validation : transaction étendue (RACCORDEMENT §3) ══════════════

    public function test_la_validation_integre_la_reception_et_passe_le_bc_partiel(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();
        $entree = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 6, 'cout_unitaire' => 100000],
        ]);

        $this->valider($entree)->assertOk();

        // Côté Stock : tout s'est passé normalement.
        $entree->refresh();
        $this->assertSame(Entree::STATUT_VALIDE, $entree->statut);
        $this->assertSame(6.0, (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite);

        // Côté Achat : le reste a bougé DANS la même validation (IA-6).
        $bon->refresh();
        $this->assertSame(BonCommande::STATUT_PARTIEL, $bon->statut);
        $this->assertSame(6.0, (float) $bon->lignes()->where('article_id', $toner->id)->first()->quantite_livree);

        // La trace d'intégration porte le numéro RÉEL de l'entrée.
        $trace = IntegrationReception::query()->where('entree_id', $entree->id)->first();
        $this->assertNotNull($trace);
        $this->assertSame($entree->numero, $trace->reference);
    }

    public function test_livrer_tout_passe_le_bc_livre(): void
    {
        [$bon, $toner, $cable] = $this->bonCommandeValide();
        $entree = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 10, 'cout_unitaire' => 100000],
            ['article_id' => $cable->id, 'quantite' => 4, 'cout_unitaire' => 5000],
        ]);

        $this->valider($entree)->assertOk();

        $this->assertSame(BonCommande::STATUT_LIVRE, $bon->refresh()->statut);
    }

    /**
     * IA-4 — le critère central : deux bons d'entrée concurrents sur le même
     * reste. Le premier passe, le second est refusé PAR LA VALIDATION (la
     * saisie datait d'avant), et son refus n'écrit RIEN : ni statut, ni
     * mouvement, ni niveau. La transaction est une : tout ou rien.
     */
    public function test_le_second_bon_sur_le_meme_reste_echoue_sans_rien_ecrire(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();

        // Deux brouillons saisis AVANT toute livraison : chacun annonce 8.
        $premier = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 8, 'cout_unitaire' => 100000],
        ]);
        $second = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 8, 'cout_unitaire' => 100000],
        ]);

        $this->valider($premier)->assertOk();

        $niveauApresPremier = (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite;
        $mouvementsApresPremier = Mouvement::query()->count();

        // Le second dépasse le reste (8 > 2) : 422, message métier d'Achat.
        $reponse = $this->valider($second, 'jeton-second')->assertUnprocessable();
        $this->assertStringContainsString('reste à livrer', $reponse->json('message'));

        // RIEN n'a été écrit par l'échec.
        $second->refresh();
        $this->assertSame(Entree::STATUT_BROUILLON, $second->statut);
        $this->assertNull($second->numero);
        $this->assertSame($mouvementsApresPremier, Mouvement::query()->count());
        $this->assertSame($niveauApresPremier, (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite);

        // Et le BC n'a été incrémenté qu'une fois.
        $this->assertSame(8.0, (float) $bon->lignes()->where('article_id', $toner->id)->first()->quantite_livree);
    }

    /** IA-5 — rejouer la validation (même jeton) n'incrémente rien. */
    public function test_rejouer_la_validation_n_incremente_pas_le_bc(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();
        $entree = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 6, 'cout_unitaire' => 100000],
        ]);

        $this->valider($entree, 'jeton-unique')->assertOk();
        $this->valider($entree, 'jeton-unique')->assertOk(); // rejeu : même réponse

        $this->assertSame(6.0, (float) $bon->lignes()->where('article_id', $toner->id)->first()->quantite_livree);
        $this->assertSame(1, IntegrationReception::query()->where('entree_id', $entree->id)->count());
    }

    /** Une entrée SANS commande fonctionne strictement comme avant. */
    public function test_une_entree_libre_ne_touche_a_aucun_bon_de_commande(): void
    {
        $article = Article::factory()->consommable()->create();
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => $article->id,
            'quantite' => 5,
            'cout_unitaire' => 2000,
        ]);

        $this->valider($entree)->assertOk();

        $this->assertSame(Entree::STATUT_VALIDE, $entree->refresh()->statut);
        $this->assertSame(0, IntegrationReception::query()->count());
    }

    // ═══ L'écran (D-11) : le formulaire porte le mode commande ══════════════

    public function test_le_formulaire_de_creation_porte_le_bouton_et_la_modale(): void
    {
        $contenu = $this->actingAs($this->magasinier)
            ->get(route('stock.entrees.create'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('btn-lier-commande', $contenu);
        $this->assertStringContainsString('selecteurCommandeModal', $contenu);
        $this->assertStringContainsString('encart-commande', $contenu);
    }

    public function test_le_formulaire_d_edition_d_un_bon_lie_transmet_le_contexte(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();
        $entree = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 2, 'cout_unitaire' => 100000],
        ]);

        $contenu = $this->actingAs($this->magasinier)
            ->get(route('stock.entrees.edit', $entree->id))
            ->assertOk()
            ->getContent();

        // Le contexte MODE_COMMANDE embarque numéro, fournisseur, plafonds.
        $this->assertStringContainsString($bon->numero, $contenu);
        $this->assertStringContainsString('"reste_a_livrer"', $contenu);
        $this->assertStringContainsString('"prix_unitaire_ht"', $contenu);
    }

    /** La fiche du BC raconte la réception : chronologie Achat après coup. */
    public function test_la_chronologie_du_bc_raconte_la_reception_integree(): void
    {
        [$bon, $toner] = $this->bonCommandeValide();
        $entree = $this->brouillonLie($bon, [
            ['article_id' => $toner->id, 'quantite' => 6, 'cout_unitaire' => 100000],
        ]);

        $this->valider($entree)->assertOk();

        $chronologie = app(\Modules\Achat\Services\ChronologieBonCommande::class)->pour($bon->refresh());
        $derniere = $chronologie->last();

        $this->assertStringContainsString('Réception '.$entree->refresh()->numero.' intégrée', $derniere['phrase']);
        $this->assertStringContainsString('6', $derniere['phrase']);
    }
}
