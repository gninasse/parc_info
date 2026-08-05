<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Services\RapportService;
use Tests\TestCase;

/**
 * États & statistiques (UX §7) : catalogue des états, restitution filtrée,
 * exports fidèles à l'écran, agrégats du tableau de bord statistique.
 */
class RapportsTest extends TestCase
{
    use RefreshDatabase;

    private Magasin $magasin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder::class);
        $this->seed(\Modules\Stock\Database\Seeders\StockPermissionsSeeder::class);

        $this->magasin = Magasin::factory()->create(['libelle' => 'Magasin Central']);
    }

    private function makeUser(array $permissions = [], string $email = 'u@example.com'): User
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

    /** Jeu de données minimal : une référence en stock, une en rupture, un mouvement de sortie. */
    private function donneesDeTest(): array
    {
        $enStock = Article::factory()->consommable()->create([
            'nom' => 'Ramette A4', 'seuil_defaut' => 5, 'prix_indicatif' => 2500,
        ]);
        $rupture = Article::factory()->consommable()->create([
            'nom' => 'Cartouche Epson', 'seuil_defaut' => 5, 'prix_indicatif' => 40000,
        ]);

        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $enStock->id, 'quantite' => 40]);
        Niveau::factory()->create(['magasin_id' => $this->magasin->id, 'article_id' => $rupture->id, 'quantite' => 0]);

        EquipementMagasin::factory()->create(['magasin_id' => $this->magasin->id]);

        return ['en_stock' => $enStock, 'rupture' => $rupture];
    }

    public function test_acces_refuse_sans_permission(): void
    {
        $this->get(route('stock.rapports.index'))->assertRedirect(route('login'));

        $sansDroit = $this->makeUser();
        $this->actingAs($sansDroit)->get(route('stock.rapports.index'))->assertStatus(403);
        $this->actingAs($sansDroit)->get(route('stock.statistiques.index'))->assertStatus(403);
    }

    public function test_export_refuse_a_qui_peut_seulement_consulter(): void
    {
        $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->get(route('stock.rapports.export', 'valorisation'))
            ->assertStatus(403);
    }

    public function test_le_catalogue_des_etats_est_affiche(): void
    {
        $reponse = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->get(route('stock.rapports.index'))
            ->assertOk();

        foreach (RapportService::catalogue() as $code => $etat) {
            $reponse->assertSee($etat['titre'], false);
            $reponse->assertSee(route('stock.rapports.show', $code), false);
        }
    }

    public function test_chaque_etat_du_catalogue_repond_a_l_ecran_et_en_json(): void
    {
        $this->donneesDeTest();
        $utilisateur = $this->makeUser(['stock.rapports.view']);

        foreach (array_keys(RapportService::catalogue()) as $code) {
            $this->actingAs($utilisateur)->get(route('stock.rapports.show', $code))->assertOk();

            $json = $this->actingAs($utilisateur)
                ->getJson(route('stock.rapports.data', $code))
                ->assertOk()
                ->json();

            $this->assertArrayHasKey('colonnes', $json, "État {$code} : colonnes manquantes.");
            $this->assertArrayHasKey('rows', $json, "État {$code} : lignes manquantes.");
            $this->assertNotEmpty($json['colonnes'], "État {$code} : aucune colonne décrite.");
        }
    }

    public function test_un_code_d_etat_inconnu_renvoie_404(): void
    {
        $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->get(route('stock.rapports.show', 'inexistant'))
            ->assertStatus(404);
    }

    public function test_l_etat_de_valorisation_totalise_la_valeur_du_stock(): void
    {
        $this->donneesDeTest();

        $json = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.rapports.data', 'valorisation'))
            ->assertOk()
            ->json();

        // Seule la ligne à quantité > 0 est valorisée : 40 × 2 500
        $this->assertSame(1, $json['total']);
        $this->assertEqualsWithDelta(100000, $json['totaux']['valeur'], 0.001);
    }

    public function test_l_etat_d_alertes_liste_ruptures_et_sous_seuil(): void
    {
        $donnees = $this->donneesDeTest();

        $json = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.rapports.data', 'alertes'))
            ->assertOk()
            ->json();

        $this->assertSame(1, $json['total']);
        $this->assertStringContainsString('Cartouche Epson', $json['rows'][0]['article']);
        $this->assertSame('⛔ Rupture', $json['rows'][0]['statut']);
        // Manque = seuil effectif (5) − stock (0)
        $this->assertEqualsWithDelta(5, $json['rows'][0]['manque'], 0.001);
    }

    public function test_le_filtre_magasin_est_pris_en_compte(): void
    {
        $this->donneesDeTest();
        $autre = Magasin::factory()->create(['libelle' => 'Magasin Annexe']);

        $json = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.rapports.data', ['code' => 'valorisation', 'magasin_id' => $autre->id]))
            ->assertOk()
            ->json();

        $this->assertSame(0, $json['total']);
        $this->assertSame('Magasin Annexe', $json['filtres_actifs']['Magasin']);
    }

    public function test_les_filtres_actifs_sont_restitues_en_clair(): void
    {
        $json = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.rapports.data', [
                'code' => 'mouvements', 'date_debut' => '2026-01-01', 'date_fin' => '2026-01-31',
            ]))
            ->assertOk()
            ->json();

        $this->assertSame('01/01/2026 → 31/01/2026', $json['filtres_actifs']['Période']);
    }

    public function test_les_exports_csv_xlsx_et_pdf_sont_produits(): void
    {
        $this->donneesDeTest();
        $utilisateur = $this->makeUser(['stock.rapports.view', 'stock.rapports.export']);

        $csv = $this->actingAs($utilisateur)
            ->get(route('stock.rapports.export', ['code' => 'valorisation', 'format' => 'csv']))
            ->assertOk();
        $this->assertStringContainsString('text/csv', $csv->headers->get('content-type'));
        $this->assertStringContainsString('Valorisation du stock', $csv->streamedContent());

        $this->actingAs($utilisateur)
            ->get(route('stock.rapports.export', ['code' => 'alertes', 'format' => 'xlsx']))
            ->assertOk();

        $pdf = $this->actingAs($utilisateur)
            ->get(route('stock.rapports.export', ['code' => 'alertes', 'format' => 'pdf']))
            ->assertOk();
        $this->assertStringContainsString('pdf', strtolower((string) $pdf->headers->get('content-type')));
    }

    /** Régression : la ligne de totaux ne doit pas casser le rendu d'une colonne typée. */
    public function test_chaque_etat_s_exporte_dans_les_trois_formats(): void
    {
        $this->donneesDeTest();
        $utilisateur = $this->makeUser(['stock.rapports.view', 'stock.rapports.export']);

        foreach (array_keys(RapportService::catalogue()) as $code) {
            foreach (['csv', 'xlsx', 'pdf'] as $format) {
                $this->actingAs($utilisateur)
                    ->get(route('stock.rapports.export', ['code' => $code, 'format' => $format]))
                    ->assertOk();
            }
        }
    }

    public function test_l_export_csv_imprime_les_filtres_actifs_en_entete(): void
    {
        $this->donneesDeTest();

        $contenu = $this->actingAs($this->makeUser(['stock.rapports.view', 'stock.rapports.export']))
            ->get(route('stock.rapports.export', [
                'code' => 'valorisation', 'format' => 'csv', 'magasin_id' => $this->magasin->id,
            ]))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Magasin : Magasin Central', $contenu);
    }

    // ── Statistiques ───────────────────────────────────────────────────────

    public function test_la_page_statistiques_repond_et_expose_ses_agregats(): void
    {
        $this->donneesDeTest();
        $utilisateur = $this->makeUser(['stock.rapports.view']);

        $this->actingAs($utilisateur)->get(route('stock.statistiques.index'))->assertOk();

        $data = $this->actingAs($utilisateur)
            ->getJson(route('stock.statistiques.data'))
            ->assertOk()
            ->json('data');

        $this->assertEqualsWithDelta(100000, $data['synthese']['valeur_stock'], 0.001);
        $this->assertSame(1, $data['synthese']['references_en_stock']);
        $this->assertSame(1, $data['synthese']['ruptures']);
        $this->assertSame(1, $data['synthese']['equipements']);
        // 2 références suivies, 1 en rupture => 50 % de disponibilité
        $this->assertEqualsWithDelta(50.0, $data['synthese']['taux_disponibilite'], 0.001);
    }

    public function test_la_serie_mensuelle_couvre_toute_la_fenetre_demandee(): void
    {
        $data = $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.statistiques.data', ['mois' => 6]))
            ->assertOk()
            ->json('data');

        $this->assertCount(6, $data['serie_mensuelle']);
        $this->assertSame(6, $data['parametres']['mois']);
    }

    public function test_la_fenetre_statistique_est_bornee(): void
    {
        $this->actingAs($this->makeUser(['stock.rapports.view']))
            ->getJson(route('stock.statistiques.data', ['mois' => 120]))
            ->assertStatus(422);
    }
}
