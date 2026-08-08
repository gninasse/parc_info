<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Modules\Catalogue\Models\Article;
use Modules\Catalogue\Models\Categorie;
use Modules\Catalogue\Models\Fournisseur;
use Tests\TestCase;

class MigrateParcInfoCommandTest extends TestCase
{
    use RefreshDatabase;

    private int $typeTonerId;

    private int $consommableTonerId;

    private int $fournisseurLegacyId;

    private int $affectationId;

    private int $licenceId;

    /**
     * Jeu de données legacy ParcInfo représentatif : 2 types, 2 consommables,
     * 1 fournisseur (avec contact principal), 1 affectation, 1 licence.
     */
    private function creerDonneesLegacy(): void
    {
        $this->typeTonerId = DB::table('parc_info_types_consommables')->insertGetId([
            'code' => 'TONER', 'nom' => 'Toners', 'categorie' => 'Impression',
            'unite_stock' => 'Cartouche', 'seul_reapprovisionnement' => 5,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $typePapierId = DB::table('parc_info_types_consommables')->insertGetId([
            'code' => 'PAPIER', 'nom' => 'Papier', 'categorie' => 'Fournitures Bureau',
            'unite_stock' => 'Rame', 'seul_reapprovisionnement' => 20,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $contactId = DB::table('parc_info_contacts')->insertGetId([
            'nom' => 'Ouédraogo', 'prenom' => 'Aïcha', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->fournisseurLegacyId = DB::table('parc_info_fournisseurs')->insertGetId([
            'code' => 'FR-001', 'nom' => 'SoftSell France', 'type' => 'Revendeur',
            'email' => 'contact@softsell.fr', 'telephone' => '+226 70 00 00 00',
            'adresse' => 'Zone du Bois', 'ville' => 'Ouagadougou', 'pays' => 'Burkina Faso',
            'contact_principal_id' => $contactId, 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $marqueId = DB::table('parc_info_marques')->insertGetId([
            'libelle' => 'HP', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->consommableTonerId = DB::table('parc_info_consommables')->insertGetId([
            'code' => 'CONS-TONER-85A', 'nom' => 'Toner HP 85A',
            'type_consommable_id' => $this->typeTonerId, 'marque_id' => $marqueId,
            'modele_reference' => 'CE285A', 'compatible_equipements' => json_encode(['HP LaserJet P1102']),
            'fournisseur_principal_id' => $this->fournisseurLegacyId,
            'cout_unitaire' => 45000, 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('parc_info_consommables')->insert([
            'code' => 'CONS-PAP-A4', 'nom' => 'Ramette A4',
            'type_consommable_id' => $typePapierId,
            'fournisseur_principal_id' => $this->fournisseurLegacyId,
            'cout_unitaire' => 3500, 'est_actif' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $equipementId = DB::table('parc_info_equipements')->insertGetId([
            'code_inventaire' => 'INV-0001', 'numero_serie' => 'SN-0001', 'modele' => 'LaserJet P1102',
            'statut' => 'en_service', 'etat' => 'bon', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->affectationId = DB::table('parc_info_affectations_consommables')->insertGetId([
            'consommable_id' => $this->consommableTonerId, 'equipement_id' => $equipementId,
            'quantite_fournie' => 2, 'date_affectation' => now()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $typeLicenceId = DB::table('parc_info_types_licences')->insertGetId([
            'code' => 'PER', 'libelle' => 'Perpétuelle', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $editeurId = DB::table('parc_info_editeurs')->insertGetId([
            'code' => 'MS', 'nom' => 'Microsoft', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $logicielId = DB::table('parc_info_logiciels')->insertGetId([
            'code' => 'WIN11', 'nom' => 'Windows 11', 'type_licence_id' => $typeLicenceId,
            'editeur_id' => $editeurId, 'created_at' => now(), 'updated_at' => now(),
        ]);
        $this->licenceId = DB::table('parc_info_licences')->insertGetId([
            'logiciel_id' => $logicielId, 'fournisseur_id' => $this->fournisseurLegacyId,
            'date_acquisition' => now()->toDateString(), 'date_expiration' => now()->addYear()->toDateString(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_dry_run_ne_conserve_aucune_ecriture(): void
    {
        $this->creerDonneesLegacy();

        $this->artisan('catalogue:migrate-parcinfo', ['--dry-run' => true])
            ->expectsOutputToContain('DRY-RUN')
            ->assertSuccessful();

        $this->assertSame(0, Categorie::count());
        $this->assertSame(0, Fournisseur::count());
        $this->assertSame(0, Article::count());
        $this->assertNull(DB::table('parc_info_affectations_consommables')->value('article_id'));
    }

    public function test_migration_reelle_complete_et_verifiee(): void
    {
        $this->creerDonneesLegacy();

        $this->artisan('catalogue:migrate-parcinfo')
            ->expectsOutputToContain('Écarts : aucun.')
            ->assertSuccessful();

        // Types → catégories niveau 1, codes conservés
        $this->assertSame(2, Categorie::count());
        $toners = Categorie::where('code', 'TONER')->first();
        $this->assertSame('Toners', $toners->libelle);
        $this->assertNull($toners->parent_id);

        // Fournisseur : code conservé, contact principal aplati
        $fournisseur = Fournisseur::where('code', 'FR-001')->first();
        $this->assertSame('SoftSell France', $fournisseur->raison_sociale);
        $this->assertSame('Aïcha Ouédraogo', $fournisseur->contact);
        $this->assertSame('Zone du Bois, Ouagadougou, Burkina Faso', $fournisseur->adresse);

        // Consommable → article : code conservé, unité/seuil descendus du type
        $article = Article::where('code', 'CONS-TONER-85A')->first();
        $this->assertSame(Article::NATURE_CONSOMMABLE, $article->nature);
        $this->assertTrue($article->est_stockable);
        $this->assertSame('Cartouche', $article->unite_stock);
        $this->assertSame('5.00', $article->seuil_defaut);
        $this->assertSame('45000.00', $article->prix_indicatif);
        $this->assertSame('CE285A', $article->reference_constructeur);
        $this->assertSame(['HP LaserJet P1102'], $article->compatibilites);
        $this->assertSame($toners->id, $article->categorie_id);
        $this->assertSame($fournisseur->id, $article->fournisseur_principal_id);

        // Affectation re-routée par code
        $this->assertSame(
            $article->id,
            (int) DB::table('parc_info_affectations_consommables')->where('id', $this->affectationId)->value('article_id')
        );

        // Licence remappée vers le fournisseur catalogue
        $this->assertSame(
            $fournisseur->id,
            (int) DB::table('parc_info_licences')->where('id', $this->licenceId)->value('fournisseur_id')
        );
    }

    public function test_la_commande_refuse_de_tourner_deux_fois(): void
    {
        $this->creerDonneesLegacy();

        $this->artisan('catalogue:migrate-parcinfo')->assertSuccessful();

        $this->artisan('catalogue:migrate-parcinfo')
            ->expectsOutputToContain('déjà été exécutée')
            ->assertFailed();
    }

    public function test_collision_de_code_c5_bloque_avec_rapport(): void
    {
        $this->creerDonneesLegacy();

        $categorie = Categorie::create(['libelle' => 'Existante']);
        Article::create([
            'code' => 'CONS-TONER-85A',
            'nom' => 'Article en collision',
            'nature' => Article::NATURE_CONSOMMABLE,
            'categorie_id' => $categorie->id,
        ]);

        $this->artisan('catalogue:migrate-parcinfo')
            ->expectsOutputToContain('Collision de codes (règle C5), migration refusée. Codes déjà présents au catalogue : CONS-TONER-85A.')
            ->assertFailed();

        $this->assertSame(0, Fournisseur::count());
    }
}
