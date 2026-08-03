<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\TamponEquipement;
use Modules\Stock\Services\SerialisationService;
use Modules\Stock\Services\TamponService;
use Tests\TestCase;

/**
 * Commit E — validation et sérialisation D10 : I9 (idempotence), I11
 * (sérialisation atomique), I14 (validation conditionnée au tampon),
 * rollback total sur échec en pleine transaction, cohérence à 0.
 */
class EntreeValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Magasinier');

        $this->magasin = Magasin::factory()->create();
    }

    /** Bon mixte : 1 quantitative (×10, 5000 F) + 1 modèle × N référencée. */
    private function bonPretAValider(int $nbUnites = 3): Entree
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 10,
            'cout_unitaire' => 5000,
        ]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create(['modele' => 'ProBook 450'])->id,
            'quantite' => $nbUnites,
            'cout_unitaire' => 350000,
        ]);

        app(TamponService::class)->passerEnReferencement($entree);

        TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->orderBy('id')->get()
            ->each(fn ($tampon, $i) => $tampon->update(['numero_serie' => 'SN-VAL-'.($i + 1)]));

        return $entree->refresh();
    }

    private function valider(Entree $entree, string $jeton = 'jeton-test')
    {
        return $this->actingAs($this->user)
            ->postJson(route('stock.entrees.valider', $entree->id), ['jeton' => $jeton]);
    }

    public function test_validation_nominale_serialisation_d10(): void
    {
        $entree = $this->bonPretAValider(3);

        $reponse = $this->valider($entree)->assertOk();

        $entree->refresh();
        $this->assertSame(Entree::STATUT_VALIDE, $entree->statut);
        $this->assertStringStartsWith('ENT-'.now()->year.'-', $entree->numero);
        $this->assertNotNull($entree->valide_le);

        // 3 fiches ParcInfo héritées de l'article, statut en stock
        $fiches = Equipement::query()->whereIn('numero_serie', ['SN-VAL-1', 'SN-VAL-2', 'SN-VAL-3'])->get();
        $this->assertCount(3, $fiches);
        $this->assertTrue($fiches->every(fn ($f) => $f->statut === 'en_stock' && $f->modele === 'ProBook 450'));
        $this->assertTrue($fiches->every(fn ($f) => str_starts_with($f->code_inventaire, 'EQP-'.now()->year.'-')));

        // 1 mouvement quantitatif + 3 unitaires, 3 rattachements, niveau à 10
        $this->assertSame(1, Mouvement::query()->whereNotNull('article_id')->count());
        $this->assertSame(3, Mouvement::query()->whereNotNull('equipement_id')->count());
        $this->assertSame(3, EquipementMagasin::query()->duMagasin($this->magasin->id)->count());
        $this->assertSame(10.0, (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite);

        // tampon purgé
        $this->assertSame(0, TamponEquipement::query()->count());

        // récapitulatif chiffré
        $this->assertSame(3, $reponse->json('recap.fiches_creees'));
        $this->assertSame(1, $reponse->json('recap.articles'));
        $this->assertEqualsWithDelta(10 * 5000 + 3 * 350000, $reponse->json('recap.total_fcfa'), 0.001);

        // DoD : cohérence à 0 après une entrée validée
        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    public function test_invariant_I9_double_post_meme_jeton_idempotent(): void
    {
        $entree = $this->bonPretAValider(2);

        $premier = $this->valider($entree, 'jeton-unique')->assertOk();
        $numero = $premier->json('recap.numero');

        // Rejouer avec le MÊME jeton : même réponse, AUCUNE écriture nouvelle
        $second = $this->valider($entree, 'jeton-unique')->assertOk();

        $this->assertSame($numero, $second->json('recap.numero'));
        $this->assertTrue($second->json('recap.deja_valide'));
        $this->assertSame(3, Mouvement::query()->count()); // 1 quantitatif + 2 unitaires, pas un de plus
        $this->assertSame(2, Equipement::query()->where('numero_serie', 'LIKE', 'SN-VAL-%')->count());

        // Un jeton DIFFÉRENT sur un bon validé → 409
        $this->valider($entree, 'autre-jeton')->assertStatus(409);
    }

    public function test_invariant_I14_tampon_incomplet_refuse(): void
    {
        $entree = $this->bonPretAValider(3);
        TamponEquipement::query()->whereNotNull('numero_serie')->orderByDesc('id')->first()->update(['numero_serie' => null]);

        $reponse = $this->valider($entree)->assertStatus(422);
        $this->assertStringContainsString('1 référence(s) manquante(s)', $reponse->json('message'));

        // Statut inchangé, rien créé
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->fresh()->statut);
        $this->assertSame(0, Mouvement::query()->count());
        $this->assertSame(0, Equipement::query()->count());
    }

    public function test_invariant_I11_doublon_parcinfo_rollback_complet(): void
    {
        $entree = $this->bonPretAValider(3);

        // Le n° de la 2e rangée existe déjà dans le parc (créé APRÈS le référencement)
        $connu = ParcInfoDeTest::equipement(['numero_serie' => 'SN-VAL-2']);

        $reponse = $this->valider($entree)->assertStatus(422);
        $this->assertStringContainsString($connu->code_inventaire, $reponse->json('message'));

        // Rollback complet : 0 fiche créée, 0 mouvement, 0 rattachement, statut inchangé
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->fresh()->statut);
        $this->assertSame(1, Equipement::query()->count()); // seul le préexistant
        $this->assertSame(0, Mouvement::query()->count());
        $this->assertSame(0, EquipementMagasin::query()->count());
        $this->assertSame(0, Niveau::query()->count());
        $this->assertSame(3, TamponEquipement::query()->whereNotNull('numero_serie')->count()); // tampon intact
    }

    public function test_echec_de_la_3e_fiche_sur_15_rollback_total(): void
    {
        $entree = $this->bonPretAValider(15);

        // Échec forcé en PLEINE transaction : la 3e création de fiche explose
        $this->app->bind(SerialisationService::class, function () {
            return new class extends SerialisationService
            {
                private int $appels = 0;

                public function creerFiche(Article $article, string $numeroSerie, ?float $valeurAchat = null): Equipement
                {
                    if (++$this->appels === 3) {
                        throw new \RuntimeException('Panne simulée à la 3e fiche');
                    }

                    return parent::creerFiche($article, $numeroSerie, $valeurAchat);
                }
            };
        });

        try {
            $this->valider($entree);
        } catch (\RuntimeException) {
            // l'exception technique remonte : c'est attendu
        }

        // Rollback TOTAL : ni les 2 fiches déjà créées, ni mouvements, ni
        // niveaux, ni rattachements ; statut inchangé, tampon intact
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->fresh()->statut);
        $this->assertNull($entree->fresh()->numero);
        $this->assertSame(0, Equipement::query()->count());
        $this->assertSame(0, Mouvement::query()->count());
        $this->assertSame(0, EquipementMagasin::query()->count());
        $this->assertSame(0, Niveau::query()->count());
        $this->assertSame(15, TamponEquipement::query()->whereNotNull('numero_serie')->count());
    }

    public function test_validation_directe_sans_equipements(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 4,
        ]);

        $this->valider($entree)->assertOk();

        $this->assertSame(Entree::STATUT_VALIDE, $entree->fresh()->statut);
        $this->assertSame(4.0, (float) Niveau::query()->first()->quantite);
        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    public function test_bon_vide_refuse(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);

        $this->valider($entree)->assertStatus(422);
        $this->assertSame(Entree::STATUT_BROUILLON, $entree->fresh()->statut);
    }

    public function test_retour_d_equipement_cloture_l_affectation(): void
    {
        // Unité en service, affectée — elle revient au magasin
        $unite = ParcInfoDeTest::equipement(['statut' => 'en_service']);
        $affectation = AffectationEquipement::create([
            'code' => 'AFF-TEST-0001',
            'equipement_id' => $unite->id,
            'date_debut' => now()->subMonth(),
            'statut' => true,
            'type_cible' => 'LOCAL',
        ]);

        $entree = Entree::factory()->retour()->create([
            'magasin_id' => $this->magasin->id,
            'observation' => 'Retour après remplacement du poste',
        ]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => null,
            'equipement_id' => $unite->id,
            'quantite' => 1,
        ]);

        $this->valider($entree)->assertOk();

        $unite->refresh();
        $affectation->refresh();
        $this->assertSame('en_stock', $unite->statut);
        $this->assertFalse((bool) $affectation->statut);
        $this->assertNotNull($affectation->date_fin);
        $this->assertSame(1, EquipementMagasin::query()->where('equipement_id', $unite->id)->count());
        $this->assertSame(1, Mouvement::query()->where('equipement_id', $unite->id)->count());
    }

    public function test_rattachement_refuse_si_unite_deja_rattachee(): void
    {
        $unite = ParcInfoDeTest::equipement();
        EquipementMagasin::factory()->create(['equipement_id' => $unite->id]);

        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => null,
            'equipement_id' => $unite->id,
            'quantite' => 1,
        ]);

        $reponse = $this->valider($entree)->assertStatus(422);
        $this->assertStringContainsString('transfert', $reponse->json('message'));
        $this->assertSame(Entree::STATUT_BROUILLON, $entree->fresh()->statut);
    }

    public function test_le_recap_est_disponible_avant_validation_sans_ecrire(): void
    {
        $entree = $this->bonPretAValider(3);

        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.entrees.valider', $entree->id), ['recap' => 1])
            ->assertOk();

        $this->assertSame(3, $reponse->json('recap.fiches_creees'));
        $this->assertSame(1, $reponse->json('recap.articles'));
        $this->assertNull($reponse->json('recap.numero'));

        // Aucune écriture : le récapitulatif est une lecture pure
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->fresh()->statut);
        $this->assertSame(0, Mouvement::query()->count());
    }
}
