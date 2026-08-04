<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\TamponEquipement;
use Modules\Stock\Services\TamponService;
use Tests\TestCase;

/**
 * Commit C — wizard de référencement : autosave unitaire (S5), unicité à
 * chaque PUT avec messages exacts UX §3.3, deep-link post-login.
 */
class EntreeWizardTest extends TestCase
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
            'name' => 'Magasinier', 'last_name' => 'Test', 'user_name' => 'magasinier',
            'email' => 'magasinier@example.com', 'password' => bcrypt('password'),
        ]);
        $this->user->assignRole('Magasinier');

        $this->entree = Entree::factory()->create(['magasin_id' => Magasin::factory()->create()->id]);
        LigneEntree::factory()->create([
            'entree_id' => $this->entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 3,
        ]);
        app(TamponService::class)->passerEnReferencement($this->entree);
        $this->entree->refresh();
    }

    private function tampons()
    {
        return TamponEquipement::query()
            ->whereIn('ligne_entree_id', $this->entree->lignes()->select('id'))
            ->orderBy('id')
            ->get();
    }

    public function test_le_wizard_affiche_progression_et_rangees(): void
    {
        $this->actingAs($this->user)
            ->get(route('stock.entrees.wizard', $this->entree->id))
            ->assertOk()
            ->assertSee('0/3')
            ->assertSee('Lignes et quantités verrouillées pendant le référencement')
            ->assertSee('Revenir au brouillon');
    }

    /**
     * Diligence 4 : plusieurs lignes « modèle × N » sur un même bon —
     * chacune propose de recevoir la saisie, et le bandeau annonce la ligne
     * active (le choix lui-même est piloté côté client).
     */
    public function test_chaque_ligne_modele_propose_de_recevoir_la_saisie(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);

        $portable = Article::factory()->equipement()->create(['nom' => 'Ordinateur portable Dell Latitude 3540']);
        $bureau = Article::factory()->equipement()->create(['nom' => 'Ordinateur de bureau HP ProDesk 400']);

        $ligneBureau = LigneEntree::factory()->create(['entree_id' => $entree->id, 'article_id' => $bureau->id, 'quantite' => 2]);
        $lignePortable = LigneEntree::factory()->create(['entree_id' => $entree->id, 'article_id' => $portable->id, 'quantite' => 3]);

        app(TamponService::class)->passerEnReferencement($entree);

        $this->actingAs($this->user)
            ->get(route('stock.entrees.wizard', $entree->id))
            ->assertOk()
            // Bandeau de la ligne active
            ->assertSee('Saisie en cours sur')
            // Un bouton de sélection par ligne, avec son libellé
            ->assertSee('Saisir sur cette ligne')
            ->assertSee('data-ligne-id="'.$ligneBureau->id.'"', false)
            ->assertSee('data-ligne-id="'.$lignePortable->id.'"', false)
            ->assertSee('data-libelle="Ordinateur portable Dell Latitude 3540"', false)
            ->assertSee('data-libelle="Ordinateur de bureau HP ProDesk 400"', false);
    }

    /** Les rangées restent rattachées à leur ligne : la saisie ne déborde pas. */
    public function test_les_rangees_sont_cloisonnees_par_ligne(): void
    {
        $entree = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);
        $ligneA = LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 2,
        ]);
        $ligneB = LigneEntree::factory()->create([
            'entree_id' => $entree->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 1,
        ]);
        app(TamponService::class)->passerEnReferencement($entree);

        $this->assertSame(2, TamponEquipement::query()->where('ligne_entree_id', $ligneA->id)->count());
        $this->assertSame(1, TamponEquipement::query()->where('ligne_entree_id', $ligneB->id)->count());

        // Saisir sur la ligne B ne consomme pas les rangées de A
        $tamponB = TamponEquipement::query()->where('ligne_entree_id', $ligneB->id)->first();
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $entree->id), [
                'tampon_id' => $tamponB->id,
                'numero_serie' => 'SN-LIGNE-B',
            ])
            ->assertOk()
            ->assertJsonPath('statut_ligne.ligne_saisis', 1)
            ->assertJsonPath('statut_ligne.ligne_total', 1)
            ->assertJsonPath('statut_ligne.saisis', 1)
            ->assertJsonPath('statut_ligne.total', 3);

        $this->assertSame(0, TamponEquipement::query()->where('ligne_entree_id', $ligneA->id)->whereNotNull('numero_serie')->count());
    }

    public function test_autosave_le_put_unitaire_persiste(): void
    {
        $tampon = $this->tampons()->first();

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => '  SN-AUTOSAVE-1  ',
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('statut_ligne.numero_serie', 'SN-AUTOSAVE-1') // trim
            ->assertJsonPath('statut_ligne.saisis', 1)
            ->assertJsonPath('statut_ligne.total', 3)
            ->assertJsonPath('statut_ligne.ligne_saisis', 1)
            ->assertJsonPath('statut_ligne.ligne_total', 3);

        $this->assertDatabaseHas('stock_tampon_equipements', [
            'id' => $tampon->id,
            'numero_serie' => 'SN-AUTOSAVE-1',
        ]);
    }

    public function test_autosave_effacement_reset_d_une_rangee(): void
    {
        $tampon = $this->tampons()->first();
        $tampon->update(['numero_serie' => 'SN-A-EFFACER', 'etat' => 'passable']);

        // Reset (bouton ✕) : PUT vide — le numéro repart, l'état choisi reste
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => '',
            ])
            ->assertOk()
            ->assertJsonPath('statut_ligne.saisis', 0);

        $tampon->refresh();
        $this->assertNull($tampon->numero_serie);
        $this->assertSame('passable', $tampon->etat);

        // La rangée resaisit librement après le reset
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => 'SN-RESAISI',
            ])
            ->assertOk()
            ->assertJsonPath('statut_ligne.saisis', 1);
    }

    public function test_autosave_de_l_etat_de_l_unite(): void
    {
        $tampon = $this->tampons()->first();

        // L'état accompagne le numéro dans le même PUT
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => 'SN-ETAT-1',
                'etat' => 'avarie',
            ])
            ->assertOk();

        $this->assertSame('avarie', $tampon->fresh()->etat);

        // Re-soumettre le MÊME numéro pour changer l'état seul ne déclenche
        // pas le contrôle d'unicité
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => 'SN-ETAT-1',
                'etat' => 'bon',
            ])
            ->assertOk();

        $this->assertSame('bon', $tampon->fresh()->etat);

        // État hors référentiel ParcInfo → 422
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => 'SN-ETAT-1',
                'etat' => 'neuf',
            ])
            ->assertStatus(422);
    }

    public function test_doublon_dans_le_meme_bon_message_ligne(): void
    {
        $tampons = $this->tampons();
        $tampons[1]->update(['numero_serie' => 'SN-DOUBLON']);

        // La rangée 2 du bon porte déjà ce numéro → « Déjà saisi ligne 2 »
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tampons[0]->id,
                'numero_serie' => 'SN-DOUBLON',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Déjà saisi ligne 2');

        $this->assertNull($tampons[0]->fresh()->numero_serie);
    }

    public function test_doublon_dans_un_autre_bon_non_valide(): void
    {
        TamponEquipement::factory()->create(['numero_serie' => 'SN-AILLEURS']);

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $this->tampons()->first()->id,
                'numero_serie' => 'SN-AILLEURS',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Déjà saisi dans un autre bon non validé');
    }

    public function test_numero_connu_de_parcinfo_renvoie_vers_le_rattachement(): void
    {
        $equipement = ParcInfoDeTest::equipement(['numero_serie' => 'SN-CONNU']);

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $this->tampons()->first()->id,
                'numero_serie' => 'SN-CONNU',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', "Existe déjà ({$equipement->code_inventaire}) — utilisez le rattachement");
    }

    public function test_put_refuse_hors_referencement(): void
    {
        app(TamponService::class)->retourBrouillon($this->entree->refresh());

        // le tampon a été purgé : on reste sur le contrôle de statut (409 avant 404)
        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => 999,
                'numero_serie' => 'SN-X',
            ])
            ->assertStatus(409);
    }

    public function test_tampon_d_un_autre_bon_introuvable(): void
    {
        $autre = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);
        $ligneAutre = LigneEntree::factory()->create([
            'entree_id' => $autre->id,
            'article_id' => Article::factory()->equipement()->create()->id,
            'quantite' => 1,
        ]);
        $tamponAutre = TamponEquipement::create(['ligne_entree_id' => $ligneAutre->id]);

        $this->actingAs($this->user)
            ->putJson(route('stock.entrees.wizard.update', $this->entree->id), [
                'tampon_id' => $tamponAutre->id,
                'numero_serie' => 'SN-INTRUS',
            ])
            ->assertStatus(404);
    }

    public function test_deep_link_le_wizard_survit_a_la_reconnexion(): void
    {
        $url = route('stock.entrees.wizard', $this->entree->id);

        // Invité → login, l'URL demandée est retenue pour le retour post-login
        $this->get($url)->assertRedirect(route('login'));
        $this->assertSame($url, session('url.intended'));

        // Connecté → le wizard s'affiche
        $this->actingAs($this->user)->get($url)->assertOk();
    }

    public function test_wizard_redirige_selon_le_statut(): void
    {
        $brouillon = Entree::factory()->create(['magasin_id' => $this->entree->magasin_id]);

        $this->actingAs($this->user)
            ->get(route('stock.entrees.wizard', $brouillon->id))
            ->assertRedirect(route('stock.entrees.edit', $brouillon->id));
    }
}
