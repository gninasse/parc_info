<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\PermissionsAchatSeeder;
use Modules\Achat\Models\Article;
use Modules\Achat\Models\BonCommande;
use Modules\Core\Models\User;
use Modules\ParcInfo\Models\Fournisseur;
use Modules\ParcInfo\Models\Marque;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Habilitations et séparation des fonctions.
 *
 * Ce cas de test n'ouvre volontairement aucune Gate : il vérifie les
 * autorisations réelles. Couvre RGC-11, ENF-SEC-02 et le scénario REC-04.
 */
class HabilitationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionsAchatSeeder::class);
    }

    /** EF-ADM-05 — Toute permission déclarée est effectivement créée. */
    public function test_les_permissions_declarees_sont_creees(): void
    {
        foreach (array_keys(config('achat.permissions')) as $nom) {
            $this->assertDatabaseHas('permissions', ['name' => $nom, 'module' => 'achat']);
        }
    }

    /** Correction AN-01 : les permissions de suppression existent réellement. */
    public function test_les_permissions_de_suppression_existent(): void
    {
        $this->assertNotNull(Permission::where('name', 'achat.bons_commande.delete')->first());
        $this->assertNotNull(Permission::where('name', 'achat.bordereaux.delete')->first());
    }

    public function test_les_roles_metier_sont_crees(): void
    {
        foreach (config('achat.roles') as $definition) {
            $this->assertNotNull(Role::where('name', $definition['name'])->first());
        }
    }

    /** RGC-11 / REC-04 — Un acheteur ne peut pas valider ses propres commandes. */
    public function test_un_acheteur_ne_peut_pas_valider_un_bon_de_commande(): void
    {
        $acheteur = User::factory()->create();
        $acheteur->assignRole('Acheteur');

        $this->assertTrue($acheteur->can('achat.bons_commande.create'));
        $this->assertTrue($acheteur->can('achat.bons_commande.edit'));

        // Le cœur de la séparation des fonctions.
        $this->assertFalse($acheteur->can('achat.bons_commande.valider'));
        $this->assertFalse($acheteur->can('achat.bons_commande.annuler'));
    }

    public function test_un_validateur_valide_mais_ne_saisit_pas(): void
    {
        $validateur = User::factory()->create();
        $validateur->assignRole('Validateur Achat');

        $this->assertTrue($validateur->can('achat.bons_commande.valider'));
        $this->assertTrue($validateur->can('achat.bons_commande.annuler'));
        $this->assertFalse($validateur->can('achat.bons_commande.create'));
    }

    public function test_un_magasinier_receptionne_mais_ne_commande_pas(): void
    {
        $magasinier = User::factory()->create();
        $magasinier->assignRole('Magasinier');

        $this->assertTrue($magasinier->can('achat.bordereaux.create'));
        $this->assertTrue($magasinier->can('achat.bordereaux.valider'));
        $this->assertFalse($magasinier->can('achat.bons_commande.create'));
        $this->assertFalse($magasinier->can('achat.articles.create'));
    }

    /** REC-04 — Le refus est effectif au niveau HTTP, pas seulement en affichage. */
    public function test_la_validation_est_refusee_a_un_acheteur_au_niveau_http(): void
    {
        $acheteur = User::factory()->create();
        $acheteur->assignRole('Acheteur');

        $bonCommande = $this->creerBonCommandeMinimal();

        $this->actingAs($acheteur)
            ->postJson(route('achat.bons-commande.valider', $bonCommande))
            ->assertForbidden();

        $this->assertSame('brouillon', $bonCommande->refresh()->statut);
    }

    public function test_la_validation_est_acceptee_pour_un_validateur(): void
    {
        $validateur = User::factory()->create();
        $validateur->assignRole('Validateur Achat');

        $bonCommande = $this->creerBonCommandeMinimal();

        $this->actingAs($validateur)
            ->postJson(route('achat.bons-commande.valider', $bonCommande))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertSame('valide', $bonCommande->refresh()->statut);
    }

    /** ENF-SEC-01 — Aucun accès sans authentification. */
    public function test_les_ecrans_sont_inaccessibles_sans_authentification(): void
    {
        $this->get(route('achat.dashboard.index'))->assertRedirect();
        $this->get(route('achat.articles.index'))->assertRedirect();
        $this->get(route('achat.bons-commande.index'))->assertRedirect();
    }

    /** ENF-SEC-04 — Un utilisateur sans permission est refusé côté serveur. */
    public function test_un_utilisateur_sans_permission_est_refuse(): void
    {
        $utilisateur = User::factory()->create();

        $this->actingAs($utilisateur)
            ->get(route('achat.articles.index'))
            ->assertForbidden();
    }

    /** EF-DOC-06 — Le dépôt de pièce jointe est soumis à habilitation. */
    public function test_le_depot_de_document_exige_une_habilitation(): void
    {
        $utilisateur = User::factory()->create();

        $this->actingAs($utilisateur)
            ->postJson(route('achat.documents.store'), [
                'documentable_type' => 'bon_commande',
                'documentable_id' => 1,
            ])
            ->assertForbidden();
    }

    /** Bon de commande minimal, créé sans passer par les habilitations. */
    protected function creerBonCommandeMinimal(): BonCommande
    {
        $marque = Marque::create(['libelle' => 'Marque test']);
        $fournisseur = Fournisseur::create([
            'code' => 'FRN-T',
            'nom' => 'Fournisseur test',
            'est_actif' => true,
        ]);

        $article = Article::create([
            'code_article' => 'ART-HAB-1',
            'designation' => 'Article de test',
            'type_article' => 'consommable',
            'marque_id' => $marque->id,
            'prix_indicatif' => 1000,
            'unite_mesure' => 'Unité',
            'taux_tva' => 18,
            'seuil_alerte' => 1,
            'actif' => true,
        ]);

        $bonCommande = BonCommande::create([
            'numero_commande' => 'BC-HAB-0001',
            'fournisseur_id' => $fournisseur->id,
            'date_commande' => now()->toDateString(),
            'statut' => 'brouillon',
        ]);

        $bonCommande->lignesCommande()->create([
            'article_id' => $article->id,
            'quantite' => 2,
            'prix_unitaire' => 1000,
            'taux_tva' => 18,
        ]);

        return $bonCommande;
    }
}
