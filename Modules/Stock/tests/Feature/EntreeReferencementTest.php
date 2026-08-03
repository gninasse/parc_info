<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Http\Controllers\EntreeController;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\TamponEquipement;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * Commit B — machine à états et verrouillage (I16/D16) au niveau HTTP.
 */
class EntreeReferencementTest extends TestCase
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

    /** Brouillon avec 1 ligne quantitative + 1 ligne modèle × 3. */
    private function brouillonMixte(): Entree
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->consommable()->create()->id,
            'quantite' => 10,
        ]);

        LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 3,
        ]);

        return $entree;
    }

    private function chargeUtileValide(Entree $entree): array
    {
        return [
            'magasin_id' => $this->magasin->id,
            'date_document' => now()->format('Y-m-d'),
            'nature' => 'livraison',
            'lignes' => [
                ['article_id' => Article::factory()->consommable()->create()->id, 'quantite' => 5],
            ],
        ];
    }

    public function test_referencement_cree_une_rangee_de_tampon_par_unite(): void
    {
        $entree = $this->brouillonMixte();

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.referencement', $entree->id))
            ->assertOk()
            ->assertJsonPath('data.wizard_url', route('stock.entrees.wizard', $entree->id));

        $entree->refresh();
        $this->assertSame(Entree::STATUT_REFERENCEMENT, $entree->statut);
        $this->assertTrue($entree->verrouille());

        // 3 rangées (la ligne quantitative n'en produit pas), toutes vides
        $tampons = TamponEquipement::query()->whereIn('ligne_entree_id', $entree->lignes()->select('id'))->get();
        $this->assertCount(3, $tampons);
        $this->assertTrue($tampons->every(fn ($t) => $t->numero_serie === null));
    }

    public function test_referencement_refuse_sans_ligne_modele(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneEntree::factory()->create(['entree_id' => $entree->id, 'quantite' => 5]);

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.referencement', $entree->id))
            ->assertStatus(422);

        $this->assertSame(Entree::STATUT_BROUILLON, $entree->fresh()->statut);
    }

    public function test_referencement_refuse_hors_brouillon(): void
    {
        $entree = Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id]);

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.referencement', $entree->id))
            ->assertStatus(409);
    }

    public function test_invariant_I16_put_verrouille_en_referencement(): void
    {
        $entree = $this->brouillonMixte();
        $this->actingAs($this->user)->postJson(route('stock.entrees.referencement', $entree->id))->assertOk();

        // PUT sur l'en-tête/lignes → 409 avec le message exact du cadenas UX
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.update', $entree->id), $this->chargeUtileValide($entree))
            ->assertStatus(409)
            ->assertJsonPath('message', EntreeController::MESSAGE_VERROUILLAGE);

        // Les lignes n'ont pas bougé
        $this->assertSame(2, $entree->lignes()->count());
    }

    public function test_invariant_I16_put_libre_en_brouillon(): void
    {
        $entree = $this->brouillonMixte();

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.update', $entree->id), $this->chargeUtileValide($entree))
            ->assertOk();

        $this->assertSame(1, $entree->lignes()->count()); // remplacement complet
    }

    public function test_invariant_I16_retour_brouillon_purge_et_journalise(): void
    {
        $entree = $this->brouillonMixte();
        $this->actingAs($this->user)->postJson(route('stock.entrees.referencement', $entree->id))->assertOk();

        // Deux références saisies avant le retour
        TamponEquipement::query()
            ->whereIn('ligne_entree_id', $entree->lignes()->select('id'))
            ->limit(2)->get()
            ->each(fn ($t, $i) => $t->update(['numero_serie' => 'SN-RETOUR-'.$i]));

        $reponse = $this->actingAs($this->user)
            ->postJson(route('stock.entrees.retour-brouillon', $entree->id))
            ->assertOk();

        // Texte SW-RETOUR-BROUILLON amendé : les articles et quantités sont conservés
        $this->assertStringContainsString('2 référence(s) saisie(s) perdue(s)', $reponse->json('message'));
        $this->assertStringContainsString('Les articles et quantités du bon sont conservés', $reponse->json('message'));

        $entree->refresh();
        $this->assertSame(Entree::STATUT_BROUILLON, $entree->statut);
        $this->assertFalse($entree->verrouille());
        $this->assertSame(0, TamponEquipement::query()->count()); // tampon purgé
        $this->assertSame(2, $entree->lignes()->count()); // lignes conservées

        // Action journalisée (activity log dédié)
        $journal = Activity::query()->where('description', 'retour_brouillon')->latest('id')->first();
        $this->assertNotNull($journal);
        $this->assertSame('stock', $journal->log_name);
        $this->assertSame(2, (int) $journal->properties['references_perdues']);

        // Déverrouillé : le PUT repasse
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.update', $entree->id), $this->chargeUtileValide($entree))
            ->assertOk();
    }

    public function test_retour_brouillon_refuse_hors_referencement(): void
    {
        $entree = $this->brouillonMixte();

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.retour-brouillon', $entree->id))
            ->assertStatus(409);
    }

    public function test_invariant_I6_put_delete_sur_valide(): void
    {
        $entree = Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id]);

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.update', $entree->id), $this->chargeUtileValide($entree))
            ->assertStatus(409);

        $this->actingAs($this->user)
            ->deleteJson(route('stock.entrees.destroy', $entree->id))
            ->assertStatus(409);

        $this->assertDatabaseHas('stock_entrees', ['id' => $entree->id, 'statut' => Entree::STATUT_VALIDE]);
    }

    public function test_suppression_en_referencement_emporte_le_tampon(): void
    {
        $entree = $this->brouillonMixte();
        $this->actingAs($this->user)->postJson(route('stock.entrees.referencement', $entree->id))->assertOk();

        $this->actingAs($this->user)
            ->deleteJson(route('stock.entrees.destroy', $entree->id))
            ->assertOk();

        $this->assertDatabaseMissing('stock_entrees', ['id' => $entree->id]);
        $this->assertSame(0, TamponEquipement::query()->count());
        $this->assertSame(0, LigneEntree::query()->count());
    }
}
