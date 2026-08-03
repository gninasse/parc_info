<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\TamponEquipement;
use Modules\Stock\Services\TamponService;
use Tests\TestCase;

/**
 * Commit F — fiche validée (UX §3.4) et bon PDF (S7/S9).
 */
class EntreeFicheTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Entree $entree;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->user = User::create([
            'name' => 'Superviseur', 'last_name' => 'Test', 'user_name' => 'superviseur',
            'email' => 'superviseur@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Superviseur stock');

        // Bon complet validé pour de vrai (fiches créées, numéro attribué)
        $this->entree = Entree::factory()->create([
            'magasin_id' => Magasin::factory()->create()->id,
            'date_document' => '2026-08-01',
            'observation_type' => 'ecart_bl',
            'observation' => 'Deux cartons humides à la réception',
        ]);

        LigneEntree::factory()->create([
            'entree_id' => $this->entree->id,
            'article_id' => Article::factory()->consommable()->create(['nom' => 'Ramette PDF'])->id,
            'quantite' => 5,
            'cout_unitaire' => 3500,
        ]);
        LigneEntree::factory()->create([
            'entree_id' => $this->entree->id,
            'article_id' => Article::factory()->equipement()->create(['modele' => 'LaserJet Pro'])->id,
            'quantite' => 2,
            'cout_unitaire' => 250000,
        ]);

        app(TamponService::class)->passerEnReferencement($this->entree);
        TamponEquipement::query()->orderBy('id')->get()
            ->each(fn ($t, $i) => $t->update(['numero_serie' => 'SN-FICHE-'.($i + 1)]));

        $this->actingAs($this->user)
            ->postJson(route('stock.entrees.valider', $this->entree->id), ['jeton' => 'jeton-fiche'])
            ->assertOk();

        $this->entree->refresh();
    }

    public function test_la_fiche_validee_affiche_tout(): void
    {
        $this->actingAs($this->user)
            ->get(route('stock.entrees.show', $this->entree->id))
            ->assertOk()
            // dates distinctes livraison / validation
            ->assertSee('Livré le 01/08/2026')
            ->assertSee('Validé le '.$this->entree->valide_le->format('d/m/Y H:i'))
            ->assertSee($this->entree->numero)
            // n° de série créés
            ->assertSee('SN-FICHE-1')
            ->assertSee('SN-FICHE-2')
            ->assertSee('LaserJet Pro')
            // observation imprimée + cartouche S9
            ->assertSee('Deux cartons humides à la réception')
            ->assertSee('Document non modifiable — corrections par contre-mouvement');
    }

    public function test_le_pdf_repond_pour_un_bon_valide(): void
    {
        $this->actingAs($this->user)
            ->get(route('stock.entrees.pdf', $this->entree->id))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_le_pdf_est_refuse_avant_validation(): void
    {
        $brouillon = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);

        $this->actingAs($this->user)
            ->getJson(route('stock.entrees.pdf', $brouillon->id))
            ->assertStatus(409);
    }

    public function test_le_gabarit_pdf_porte_les_bons_champs(): void
    {
        // Assertion sur le HTML du gabarit (sans dompdf) — champs UX §3.4/S9
        $html = view('stock::pdf.entree', [
            'entree' => $this->entree->fresh(['magasin', 'fournisseur', 'lignes.article', 'createur', 'valideur']),
            'unites' => collect([
                ['code_inventaire' => 'EQP-2026-0001', 'modele' => 'LaserJet Pro', 'numero_serie' => 'SN-FICHE-1', 'url_fiche' => null],
            ]),
        ])->render();

        $this->assertStringContainsString('CHU-YO', $html);
        $this->assertStringContainsString($this->entree->numero, $html);
        $this->assertStringContainsString('Date de livraison', $html);
        $this->assertStringContainsString('01/08/2026', $html);
        $this->assertStringContainsString('Date de validation', $html);
        $this->assertStringContainsString('SN-FICHE-1', $html);
        $this->assertStringContainsString('Ramette PDF', $html);
        $this->assertStringContainsString('Deux cartons humides à la réception', $html);
        $this->assertStringContainsString('Signature du livreur', $html);
        $this->assertStringContainsString('Document non modifiable — corrections par contre-mouvement', $html);
    }

    public function test_show_d_un_brouillon_redirige_vers_l_edition(): void
    {
        $brouillon = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);

        $this->actingAs($this->user)
            ->get(route('stock.entrees.show', $brouillon->id))
            ->assertRedirect(route('stock.entrees.edit', $brouillon->id));
    }
}
