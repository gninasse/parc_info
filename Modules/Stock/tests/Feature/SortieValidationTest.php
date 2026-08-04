<?php

namespace Modules\Stock\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Organisation\Models\Service;
use Modules\ParcInfo\Models\AffectationEquipement;
use Modules\ParcInfo\Models\CategorieEquipement;
use Modules\Stock\Database\Factories\ParcInfoDeTest;
use Modules\Stock\Models\Entree;
use Modules\Stock\Models\EquipementMagasin;
use Modules\Stock\Models\LigneSortie;
use Modules\Stock\Models\Magasin;
use Modules\Stock\Models\Mouvement;
use Modules\Stock\Models\Niveau;
use Modules\Stock\Models\Sortie;
use Modules\Stock\Services\MouvementService;
use Tests\TestCase;

/**
 * Sorties — validation : I15 (disponible re-contrôlé ligne à ligne),
 * I17 (complétude), D8 (affectation + en service + détachement), I9.
 */
class SortieValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Magasin $magasin;

    private Article $article;

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
        $this->article = Article::factory()->consommable()->create(['seuil_defaut' => null]);
    }

    private function approvisionner(float $quantite): void
    {
        app(MouvementService::class)->entree([
            'entree_id' => Entree::factory()->validee()->create(['magasin_id' => $this->magasin->id])->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => $quantite,
        ]);
    }

    private function valider(Sortie $sortie, string $jeton = 'jeton-sortie')
    {
        return $this->actingAs($this->user)
            ->postJson(route('stock.sorties.valider', $sortie->id), ['jeton' => $jeton]);
    }

    /** Service organisationnel valide (direction_id NOT NULL, site seedé par la migration). */
    private function creerService(string $code, string $libelle): Service
    {
        $direction = \Modules\Organisation\Models\Direction::query()->firstOrCreate(
            ['code' => 'DIR-TEST'],
            ['libelle' => 'Direction test', 'site_id' => \Modules\Organisation\Models\Site::query()->value('id')]
        );

        return Service::create([
            'code' => $code,
            'libelle' => $libelle,
            'direction_id' => $direction->id,
            'type_service' => 'administratif',
        ]);
    }

    public function test_validation_nominale_quantitative_avec_denormalisation(): void
    {
        $this->approvisionner(10);
        $service = $this->creerService('SRV-T', 'Service test');

        $sortie = Sortie::factory()->create([
            'magasin_id' => $this->magasin->id,
            'beneficiaire_type' => 'service',
            'beneficiaire_service_id' => $service->id,
            'beneficiaire_libelle' => null,
        ]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $this->article->id, 'quantite' => 4]);

        $this->valider($sortie)->assertOk();

        $sortie->refresh();
        $this->assertSame(Sortie::STATUT_VALIDE, $sortie->statut);
        $this->assertStringStartsWith('SOR-'.now()->year.'-', $sortie->numero);
        $this->assertSame($service->libelle, $sortie->beneficiaire_libelle); // dénormalisé, survivra au set null
        $this->assertSame(6.0, (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite);

        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    public function test_invariant_I15_disponible_recontrole_ligne_par_ligne(): void
    {
        $this->approvisionner(8);
        $autreArticle = Article::factory()->consommable()->create(['seuil_defaut' => null]);

        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $this->article->id, 'quantite' => 5]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $autreArticle->id, 'quantite' => 3]);

        // Le stock tombe à 3 APRÈS l'enregistrement du brouillon (dispo était 8)
        app(MouvementService::class)->sortie([
            'sortie_id' => Sortie::factory()->validee()->create(['magasin_id' => $this->magasin->id])->id,
            'magasin_id' => $this->magasin->id,
            'article_id' => $this->article->id,
            'quantite' => 5,
        ]);

        $reponse = $this->valider($sortie)->assertStatus(422);

        // 422 listant LA ligne fautive (la seconde n'a aucun stock non plus mais
        // c'est la première demandée qui est nommée — toutes les fautives listées)
        $this->assertStringContainsString('ligne 1', $reponse->json('message'));
        $this->assertStringContainsString($this->article->nom, $reponse->json('message'));

        // Aucun mouvement de CE bon, statut inchangé
        $this->assertSame(0, Mouvement::query()->where('sortie_id', $sortie->id)->count());
        $this->assertSame(Sortie::STATUT_BROUILLON, $sortie->fresh()->statut);
    }

    public function test_invariant_I17_pointage_incomplet_bloque_la_validation(): void
    {
        $modele = Article::factory()->equipement()->create();
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id, 'remis_a_nom' => 'A. Porteur']);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $modele->id, 'quantite' => 2]);
        $sortie->passerEnPointage();

        $reponse = $this->valider($sortie)->assertStatus(422);
        $this->assertStringContainsString('2 unité(s) restant à pointer', $reponse->json('message'));
        $this->assertSame(Sortie::STATUT_POINTAGE, $sortie->fresh()->statut);
    }

    public function test_remis_a_requis_si_equipements(): void
    {
        $modele = Article::factory()->equipement()->create();
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id, 'remis_a_nom' => null]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $modele->id, 'quantite' => 1]);

        $reponse = $this->valider($sortie)->assertStatus(422);
        $this->assertStringContainsString('Remis à', $reponse->json('message'));
    }

    public function test_d8_sortie_d_equipement_affectation_detachement_en_service(): void
    {
        $modele = Article::factory()->equipement()->create(['modele' => 'Latitude 5440']);
        $categorie = CategorieEquipement::query()->find($modele->categorie_equipement_id);

        $unite = ParcInfoDeTest::equipement(['categorie_id' => $categorie->id, 'modele' => 'Latitude 5440']);
        EquipementMagasin::create(['equipement_id' => $unite->id, 'magasin_id' => $this->magasin->id, 'date_rattachement' => now()]);

        $service = $this->creerService('SRV-D8', 'Service D8');

        $sortie = Sortie::factory()->create([
            'magasin_id' => $this->magasin->id,
            'beneficiaire_type' => 'service',
            'beneficiaire_service_id' => $service->id,
            'remis_a_nom' => 'B. Porteur',
        ]);
        $ligne = LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $modele->id, 'quantite' => 1]);
        $sortie->passerEnPointage();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $ligne->id, 'equipement_id' => $unite->id,
        ])->assertOk();

        $this->valider($sortie)->assertOk();

        // D8 : affectation créée (cible = bénéficiaire), unité en service, détachée du magasin
        $affectation = AffectationEquipement::query()->where('equipement_id', $unite->id)->where('statut', true)->first();
        $this->assertNotNull($affectation);
        $this->assertSame($service->id, (int) $affectation->service_id);
        $this->assertSame('SERVICE', $affectation->niveau_rattachement);

        $this->assertSame('en_service', $unite->fresh()->statut);
        $this->assertSame(0, EquipementMagasin::query()->where('equipement_id', $unite->id)->count());

        // Référence croisée : le mouvement porte l'affectation ; tampon purgé
        $mouvement = Mouvement::query()->where('equipement_id', $unite->id)->first();
        $this->assertSame($affectation->id, (int) $mouvement->affectation_equipement_id);
        $this->assertSame(0, \Modules\Stock\Models\TamponEquipement::query()->count());

        $this->artisan('stock:controle-coherence')->assertExitCode(0);
    }

    public function test_invariant_I9_idempotence_par_jeton(): void
    {
        $this->approvisionner(10);
        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $this->article->id, 'quantite' => 2]);

        $premier = $this->valider($sortie, 'jeton-x')->assertOk();
        $second = $this->valider($sortie, 'jeton-x')->assertOk();

        $this->assertSame($premier->json('recap.numero'), $second->json('recap.numero'));
        $this->assertSame(2, Mouvement::query()->count()); // 1 entrée + 1 sortie, pas plus
        $this->assertSame(8.0, (float) Niveau::query()->duMagasin($this->magasin->id)->first()->quantite);

        $this->valider($sortie, 'jeton-different')->assertStatus(409);
    }

    public function test_les_deux_modeles_d_impression_du_bon_de_sortie(): void
    {
        // Bon mixte validé : 1 article quantitatif + 1 unité pointée
        $this->approvisionner(10);

        $modele = Article::factory()->equipement()->create(['modele' => 'Latitude 7440']);
        $categorie = CategorieEquipement::query()->find($modele->categorie_equipement_id);
        $unite = ParcInfoDeTest::equipement([
            'categorie_id' => $categorie->id, 'modele' => 'Latitude 7440', 'numero_serie' => 'SN-PDF-SOR',
        ]);
        EquipementMagasin::create(['equipement_id' => $unite->id, 'magasin_id' => $this->magasin->id, 'date_rattachement' => now()]);

        $service = $this->creerService('SRV-PDF', 'Service PDF');
        $sortie = Sortie::factory()->create([
            'magasin_id' => $this->magasin->id,
            'beneficiaire_type' => 'service',
            'beneficiaire_service_id' => $service->id,
            'remis_a_nom' => 'D. Porteur',
        ]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $this->article->id, 'quantite' => 3]);
        $ligneModele = LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $modele->id, 'quantite' => 1]);
        $sortie->passerEnPointage();

        $this->actingAs($this->user)->putJson(route('stock.sorties.pointage.update', $sortie->id), [
            'action' => 'pointer', 'ligne_id' => $ligneModele->id, 'equipement_id' => $unite->id,
        ])->assertOk();

        $this->valider($sortie)->assertOk();

        // Les deux modèles répondent en PDF, plus le repli sur défaut
        foreach (['articles', 'equipements', 'inexistant'] as $variante) {
            $this->actingAs($this->user)
                ->get(route('stock.sorties.pdf', ['id' => $sortie->id, 'modele' => $variante]))
                ->assertOk()
                ->assertHeader('content-type', 'application/pdf');
        }

        $donnees = [
            'sortie' => $sortie->fresh(['magasin', 'lignes.article', 'lignes.emplacementLocal', 'remisAEmploye', 'createur', 'valideur']),
            'unites' => collect([[
                'code_inventaire' => 'EQP-2026-0009', 'modele' => 'Latitude 7440',
                'numero_serie' => 'SN-PDF-SOR', 'affectation_code' => 'AFF-XYZ', 'url_fiche' => null,
            ]]),
        ];

        // Modèle 1 : libellés, quantités, signature du porteur
        $articles = view('stock::pdf.sortie_articles', $donnees)->render();
        $this->assertStringContainsString('CHU-YO', $articles);
        $this->assertStringContainsString($sortie->fresh()->numero, $articles);
        $this->assertStringContainsString($this->article->nom, $articles);
        $this->assertStringContainsString('Quantité', $articles);
        $this->assertStringContainsString('Service PDF', $articles);
        $this->assertStringContainsString('Signature du porteur — D. Porteur', $articles);
        $this->assertStringContainsString('Modèle × 1', $articles);
        $this->assertStringContainsString('Document non modifiable — corrections par contre-mouvement', $articles);

        // Modèle 2 : code inventaire, modèle, n° de série, affectation
        $equipements = view('stock::pdf.sortie_equipements', $donnees)->render();
        $this->assertStringContainsString('Fiche des équipements', $equipements);
        $this->assertStringContainsString('Code inventaire', $equipements);
        $this->assertStringContainsString('EQP-2026-0009', $equipements);
        $this->assertStringContainsString('Latitude 7440', $equipements);
        $this->assertStringContainsString('SN-PDF-SOR', $equipements);
        $this->assertStringContainsString('AFF-XYZ', $equipements);
        $this->assertStringContainsString('Reçu par — D. Porteur', $equipements);
        $this->assertStringContainsString('Document non modifiable — corrections par contre-mouvement', $equipements);

        // Bon sans unité : le modèle équipements reste imprimable
        $vide = view('stock::pdf.sortie_equipements', ['sortie' => $donnees['sortie'], 'unites' => collect()])->render();
        $this->assertStringContainsString('Aucun équipement sur ce bon', $vide);
    }

    public function test_le_recap_signale_les_seuils_franchis(): void
    {
        $this->article->update(['seuil_defaut' => 5]);
        $this->approvisionner(6);

        $sortie = Sortie::factory()->create(['magasin_id' => $this->magasin->id]);
        LigneSortie::factory()->create(['sortie_id' => $sortie->id, 'article_id' => $this->article->id, 'quantite' => 3]);

        $reponse = $this->valider($sortie)->assertOk();

        // Avertissement non bloquant (§7.4) : niveau 3 ≤ seuil 5
        $alertes = $reponse->json('recap.alertes_seuil');
        $this->assertCount(1, $alertes);
        $this->assertSame('SOUS_SEUIL', $alertes[0]['statut']);
    }
}
