<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Equipement;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneEntree;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\TamponEquipement;
use Tests\TestCase;

/**
 * Récapitulatif du prompt : matrice de permissions sur TOUTES les routes
 * entrées + I13 exhaustif au niveau HTTP.
 */
class EntreeRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);
    }

    private function makeUser(array $permissions = [], string $email = 'test@example.com'): User
    {
        $user = User::create([
            'name' => 'Test', 'last_name' => 'User', 'user_name' => 'user_'.md5($email),
            'email' => $email, 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    /**
     * SFD §5 : store couvre création, référencement, validation, PDF.
     * [méthode, route, params({id} → entrée), permission]
     */
    public static function routesProtegees(): array
    {
        return [
            'index' => ['GET', 'stock.entrees.index', [], 'stock.entrees.index'],
            'data' => ['GET', 'stock.entrees.data', [], 'stock.entrees.index'],
            'show' => ['GET', 'stock.entrees.show', ['id' => true], 'stock.entrees.index'],
            'create' => ['GET', 'stock.entrees.create', [], 'stock.entrees.store'],
            'store' => ['POST', 'stock.entrees.store', [], 'stock.entrees.store'],
            'edit' => ['GET', 'stock.entrees.edit', ['id' => true], 'stock.entrees.update'],
            'update' => ['PUT', 'stock.entrees.update', ['id' => true], 'stock.entrees.update'],
            'destroy' => ['DELETE', 'stock.entrees.destroy', ['id' => true], 'stock.entrees.destroy'],
            'referencement' => ['POST', 'stock.entrees.referencement', ['id' => true], 'stock.entrees.store'],
            'wizard' => ['GET', 'stock.entrees.wizard', ['id' => true], 'stock.entrees.store'],
            'wizard.update' => ['PUT', 'stock.entrees.wizard.update', ['id' => true], 'stock.entrees.store'],
            'wizard.import' => ['POST', 'stock.entrees.wizard.import', ['id' => true], 'stock.entrees.store'],
            'retour-brouillon' => ['POST', 'stock.entrees.retour-brouillon', ['id' => true], 'stock.entrees.store'],
            'valider' => ['POST', 'stock.entrees.valider', ['id' => true], 'stock.entrees.store'],
            'pdf' => ['GET', 'stock.entrees.pdf', ['id' => true], 'stock.entrees.store'],
            'equipements disponibles' => ['GET', 'stock.equipements.disponibles', [], 'stock.entrees.store'],
        ];
    }

    /** @dataProvider routesProtegees */
    public function test_matrice_403_sans_permission_et_redirection_invite(string $methode, string $route, array $params, string $permission): void
    {
        $entree = Entree::factory()->create(['magasin_id' => Magasin::factory()->create()->id]);
        $params = array_map(fn ($valeur) => $valeur === true ? $entree->id : $valeur, $params);
        $url = route($route, $params);

        // Invité → login
        $this->call($methode, $url)->assertRedirect(route('login'));

        // Sans permission → 403, jamais 200 ni 500
        $sans = $this->makeUser([], 'sans-'.md5($route.$methode).'@example.com');
        $this->actingAs($sans)->json($methode, $url)->assertStatus(403);

        // Avec la permission → jamais 403 ni 500 (2xx, redirection ou refus métier 409/422)
        $avec = $this->makeUser([$permission], 'avec-'.md5($route.$methode).'@example.com');
        $statut = $this->actingAs($avec)->json($methode, $url)->getStatusCode();
        $this->assertContains($statut, [200, 201, 302, 409, 422], "Route {$route} : statut {$statut} inattendu avec permission.");
    }

    /**
     * DoD — parcours complet « camion → déballage → officialisation »,
     * intégralement au niveau HTTP : brouillon → référencement → saisies
     * unitaires (chaque PUT est committé : une coupure serveur ne perd
     * aucun numéro) → validation → fiche → PDF → cohérence à 0.
     */
    public function test_parcours_complet_camion_deballage_officialisation(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $modele = Article::factory()->equipement()->create(['modele' => 'EliteBook 840']);
        $user = $this->makeUser([
            'stock.entrees.index', 'stock.entrees.store', 'stock.entrees.update', 'stock.entrees.destroy',
        ]);

        // 1 · Le camion arrive : brouillon enregistré au clic
        $id = $this->actingAs($user)->postJson(route('stock.entrees.store'), [
            'magasin_id' => $magasin->id,
            'date_document' => now()->format('Y-m-d'),
            'nature' => 'livraison',
            'observation_type' => 'livraison_conforme',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 6, 'cout_unitaire' => 2500],
                ['article_id' => $modele->id, 'quantite' => 2, 'cout_unitaire' => 480000],
            ],
        ])->assertOk()->json('data.id');

        // 2 · Déballage : référencement puis saisie unitaire (autosave committé)
        $this->actingAs($user)->postJson(route('stock.entrees.referencement', $id))->assertOk();

        foreach (TamponEquipement::query()->orderBy('id')->get() as $index => $tampon) {
            $this->actingAs($user)->putJson(route('stock.entrees.wizard.update', $id), [
                'tampon_id' => $tampon->id,
                'numero_serie' => 'SN-E2E-'.($index + 1),
            ])->assertOk();

            // « Coupure serveur » : la saisie est déjà en base, rien à perdre
            $this->assertDatabaseHas('stock_tampon_equipements', ['numero_serie' => 'SN-E2E-'.($index + 1)]);
        }

        // 3 · Officialisation : SW-VALIDER-ENT (recap) puis validation idempotente
        $recap = $this->actingAs($user)
            ->postJson(route('stock.entrees.valider', $id), ['recap' => 1])
            ->assertOk()->json('recap');
        $this->assertSame(2, $recap['fiches_creees']);

        $this->actingAs($user)
            ->postJson(route('stock.entrees.valider', $id), ['jeton' => 'jeton-e2e'])
            ->assertOk();

        // Fiche + PDF disponibles, journal cohérent
        $this->actingAs($user)->get(route('stock.entrees.show', $id))
            ->assertOk()->assertSee('SN-E2E-1')->assertSee('EliteBook 840');
        $this->actingAs($user)->get(route('stock.entrees.pdf', $id))
            ->assertOk()->assertHeader('content-type', 'application/pdf');

        $this->assertSame(6.0, (float) Niveau::query()->duMagasin($magasin->id)->first()->quantite);
        $this->assertSame(2, EquipementMagasin::query()->count());
        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    /**
     * I13 exhaustif au niveau HTTP : un brouillon complet créé, modifié,
     * référencé, ramené au brouillon puis supprimé ne laisse RIEN.
     */
    public function test_invariant_I13_brouillon_sans_effet_compteurs_exhaustifs(): void
    {
        $magasin = Magasin::factory()->create();
        $article = Article::factory()->consommable()->create();
        $modele = Article::factory()->equipement()->create();
        $user = $this->makeUser(['stock.entrees.store', 'stock.entrees.update', 'stock.entrees.destroy']);

        $compteurs = fn (): array => [
            'mouvements' => Mouvement::query()->count(),
            'niveaux' => Niveau::query()->count(),
            'equipements' => Equipement::query()->count(),
            'rattachements' => EquipementMagasin::query()->count(),
            'sequences' => (int) DB::table('stock_sequences')->sum('last_value'),
        ];

        $avant = $compteurs();

        // Création avec lignes
        $id = $this->actingAs($user)->postJson(route('stock.entrees.store'), [
            'magasin_id' => $magasin->id,
            'date_document' => now()->format('Y-m-d'),
            'nature' => 'livraison',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 12, 'cout_unitaire' => 900],
                ['article_id' => $modele->id, 'quantite' => 2, 'cout_unitaire' => 120000],
            ],
        ])->assertOk()->json('data.id');

        // Modification
        $this->actingAs($user)->putJson(route('stock.entrees.update', $id), [
            'magasin_id' => $magasin->id,
            'date_document' => now()->format('Y-m-d'),
            'nature' => 'livraison',
            'reference_externe' => 'BL-I13',
            'lignes' => [
                ['article_id' => $article->id, 'quantite' => 20],
                ['article_id' => $modele->id, 'quantite' => 2],
            ],
        ])->assertOk();

        // Aller-retour en référencement avec une saisie
        $this->actingAs($user)->postJson(route('stock.entrees.referencement', $id))->assertOk();
        $tampon = TamponEquipement::query()->first();
        $this->actingAs($user)->putJson(route('stock.entrees.wizard.update', $id), [
            'tampon_id' => $tampon->id,
            'numero_serie' => 'SN-I13',
        ])->assertOk();
        $this->actingAs($user)->postJson(route('stock.entrees.retour-brouillon', $id))->assertOk();

        // Suppression
        $this->actingAs($user)->deleteJson(route('stock.entrees.destroy', $id))->assertOk();

        // Compteurs exhaustifs : rien n'a bougé, aucun numéro consommé
        $this->assertSame($avant, $compteurs());
        $this->assertSame(0, Entree::query()->count());
        $this->assertSame(0, LigneEntree::query()->count());
        $this->assertSame(0, TamponEquipement::query()->count());
    }
}
