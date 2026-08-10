<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\ContactFournisseur;
use Modules\Catalogue\Models\Fournisseur;
use Modules\Core\Models\User;
use Tests\TestCase;

/**
 * Les contacts d'un fournisseur (onglet de la fiche).
 *
 * Ce que cette suite protège, par ordre d'importance :
 *
 *   1. **le cloisonnement entre fournisseurs** — un identifiant deviné ne doit
 *      pas donner accès au carnet d'adresses d'un autre fournisseur ;
 *   2. **la règle « un seul contact principal »**, qui n'est pas tenue par la
 *      base (un index unique partiel ne s'écrit pas pareil sous SQLite et
 *      PostgreSQL) et repose donc entièrement sur le service ;
 *   3. **les permissions serveur**, y compris quand l'interface a masqué le
 *      bouton correspondant.
 */
class ContactsFournisseurTest extends TestCase
{
    use RefreshDatabase;

    private User $gestionnaire;

    private Fournisseur $fournisseur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);

        $this->gestionnaire = $this->utilisateur([
            'catalogue.fournisseurs.index',
            'catalogue.contacts.index',
            'catalogue.contacts.store',
            'catalogue.contacts.update',
            'catalogue.contacts.destroy',
        ], 'gestionnaire_contacts');

        $this->fournisseur = Fournisseur::factory()->create(['raison_sociale' => 'SoftSell']);
    }

    private function utilisateur(array $permissions, string $cle): User
    {
        $user = User::create([
            'name' => ucfirst($cle), 'last_name' => 'Test', 'user_name' => $cle,
            'email' => $cle.'@example.com', 'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    private function donnees(array $remplacements = []): array
    {
        return array_merge([
            'nom' => 'Ouédraogo',
            'prenom' => 'Awa',
            'fonction' => 'Commerciale',
            'telephone' => '+226 70 00 00 00',
            'email' => 'awa@softsell.bf',
        ], $remplacements);
    }

    // ── Le cloisonnement entre fournisseurs ────────────────────────────────

    /**
     * LE verrou de sécurité du lot. Les routes sont imbriquées et portent
     * `scopeBindings()` : un contact qui n'appartient pas au fournisseur de
     * l'URL est introuvable, et non « modifiable par erreur ».
     */
    public function test_on_ne_peut_pas_modifier_le_contact_d_un_autre_fournisseur(): void
    {
        $autre = Fournisseur::factory()->create(['raison_sociale' => 'Concurrent']);
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $autre->id]);

        $this->actingAs($this->gestionnaire)
            ->putJson(
                route('catalogue.contacts.update', [$this->fournisseur->id, $contact->id]),
                $this->donnees(['nom' => 'Détourné'])
            )
            ->assertNotFound();

        $this->assertSame(
            $contact->nom,
            $contact->fresh()->nom,
            "Le contact de l'autre fournisseur ne doit pas avoir bougé."
        );
    }

    public function test_on_ne_peut_pas_supprimer_le_contact_d_un_autre_fournisseur(): void
    {
        $autre = Fournisseur::factory()->create();
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $autre->id]);

        $this->actingAs($this->gestionnaire)
            ->deleteJson(route('catalogue.contacts.destroy', [$this->fournisseur->id, $contact->id]))
            ->assertNotFound();

        $this->assertModelExists($contact);
    }

    public function test_la_liste_ne_montre_que_les_contacts_du_fournisseur(): void
    {
        ContactFournisseur::factory()->count(2)->create(['fournisseur_id' => $this->fournisseur->id]);
        ContactFournisseur::factory()->create([
            'fournisseur_id' => Fournisseur::factory()->create()->id,
            'nom' => 'Intrus',
        ]);

        $reponse = $this->actingAs($this->gestionnaire)
            ->getJson(route('catalogue.contacts.index', $this->fournisseur->id))
            ->assertOk();

        $this->assertSame(2, $reponse->json('total'));
        $this->assertStringNotContainsString('Intrus', $reponse->getContent());
    }

    // ── La règle « un seul principal » ─────────────────────────────────────

    /**
     * Le premier contact devient principal tout seul : sans cela, on aurait
     * des fournisseurs pourvus de contacts mais sans interlocuteur désigné.
     */
    public function test_le_premier_contact_devient_principal(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('catalogue.contacts.store', $this->fournisseur->id), $this->donnees())
            ->assertOk();

        $this->assertTrue($this->fournisseur->contacts()->first()->est_principal);
    }

    /** Le second, non : il ne doit pas déloger le premier sans qu'on le demande. */
    public function test_le_second_contact_n_est_pas_principal_par_defaut(): void
    {
        ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->postJson(route('catalogue.contacts.store', $this->fournisseur->id), $this->donnees(['nom' => 'Second']))
            ->assertOk();

        $this->assertSame(1, $this->fournisseur->contacts()->where('est_principal', true)->count());
    }

    /**
     * LE test central : quel que soit le chemin, il ne peut jamais y avoir
     * deux principaux. La base ne l'empêche pas, seul le service le tient.
     */
    public function test_designer_un_principal_demarque_l_ancien(): void
    {
        $ancien = ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);
        $nouveau = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->patchJson(route('catalogue.contacts.principal', [$this->fournisseur->id, $nouveau->id]))
            ->assertOk();

        $this->assertTrue($nouveau->fresh()->est_principal);
        $this->assertFalse($ancien->fresh()->est_principal);
        $this->assertSame(1, $this->fournisseur->contacts()->where('est_principal', true)->count());
    }

    public function test_cocher_principal_a_la_modification_demarque_l_ancien(): void
    {
        $ancien = ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);
        $autre = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->putJson(
                route('catalogue.contacts.update', [$this->fournisseur->id, $autre->id]),
                $this->donnees(['est_principal' => true])
            )
            ->assertOk();

        $this->assertFalse($ancien->fresh()->est_principal);
        $this->assertSame(1, $this->fournisseur->contacts()->where('est_principal', true)->count());
    }

    /**
     * Désactiver un contact le retire d'office du rôle de principal : la
     * fiche mettrait sinon en avant quelqu'un qu'on a cessé d'appeler.
     */
    public function test_desactiver_le_principal_lui_retire_ce_role(): void
    {
        $contact = ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->putJson(
                route('catalogue.contacts.update', [$this->fournisseur->id, $contact->id]),
                $this->donnees(['est_actif' => false, 'est_principal' => true])
            )
            ->assertOk();

        $contact->refresh();

        $this->assertFalse($contact->est_actif);
        $this->assertFalse($contact->est_principal, 'Un contact inactif ne peut pas rester principal.');
    }

    /**
     * Supprimer le principal promeut le suivant : laisser le fournisseur sans
     * interlocuteur obligerait l'utilisateur à y penser, et personne n'y pense.
     */
    public function test_supprimer_le_principal_promeut_un_remplacant(): void
    {
        $principal = ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);
        $suivant = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->deleteJson(route('catalogue.contacts.destroy', [$this->fournisseur->id, $principal->id]))
            ->assertOk();

        $this->assertTrue($suivant->fresh()->est_principal);
    }

    /** Un contact inactif ne doit pas être promu à la place du principal supprimé. */
    public function test_le_remplacant_promu_est_un_contact_actif(): void
    {
        $principal = ContactFournisseur::factory()->principal()->create(['fournisseur_id' => $this->fournisseur->id]);
        $inactif = ContactFournisseur::factory()->inactif()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->deleteJson(route('catalogue.contacts.destroy', [$this->fournisseur->id, $principal->id]))
            ->assertOk();

        $this->assertFalse($inactif->fresh()->est_principal);
    }

    // ── Le CRUD ────────────────────────────────────────────────────────────

    public function test_ajouter_un_contact(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('catalogue.contacts.store', $this->fournisseur->id), $this->donnees())
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('catalogue_contacts_fournisseur', [
            'fournisseur_id' => $this->fournisseur->id,
            'nom' => 'Ouédraogo',
            'fonction' => 'Commerciale',
        ]);
    }

    public function test_le_nom_est_obligatoire(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(route('catalogue.contacts.store', $this->fournisseur->id), $this->donnees(['nom' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('nom');
    }

    public function test_l_email_doit_etre_valide(): void
    {
        $this->actingAs($this->gestionnaire)
            ->postJson(
                route('catalogue.contacts.store', $this->fournisseur->id),
                $this->donnees(['email' => 'pas-une-adresse'])
            )
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_modifier_un_contact(): void
    {
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->putJson(
                route('catalogue.contacts.update', [$this->fournisseur->id, $contact->id]),
                $this->donnees(['fonction' => 'Directeur commercial'])
            )
            ->assertOk();

        $this->assertSame('Directeur commercial', $contact->fresh()->fonction);
    }

    public function test_supprimer_un_contact(): void
    {
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->deleteJson(route('catalogue.contacts.destroy', [$this->fournisseur->id, $contact->id]))
            ->assertOk();

        $this->assertModelMissing($contact);
    }

    /**
     * Un contact n'a aucun sens sans son fournisseur : la cascade est voulue,
     * contrairement aux pièces jointes d'un bon de commande qui, elles, se
     * conservent.
     */
    public function test_supprimer_le_fournisseur_emporte_ses_contacts(): void
    {
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->fournisseur->delete();

        $this->assertModelMissing($contact);
    }

    // ── Les permissions, côté serveur ──────────────────────────────────────

    public function test_sans_permission_de_lecture_la_liste_est_refusee(): void
    {
        $intrus = $this->utilisateur(['catalogue.fournisseurs.index'], 'intrus_contacts');

        $this->actingAs($intrus)
            ->getJson(route('catalogue.contacts.index', $this->fournisseur->id))
            ->assertForbidden();
    }

    /**
     * Le bouton est masqué par la vue, mais la route doit refuser d'elle-même :
     * masquer n'est pas protéger.
     */
    public function test_sans_permission_d_ecriture_l_ajout_est_refuse(): void
    {
        $lecteur = $this->utilisateur([
            'catalogue.fournisseurs.index', 'catalogue.contacts.index',
        ], 'lecteur_contacts');

        $this->actingAs($lecteur)
            ->postJson(route('catalogue.contacts.store', $this->fournisseur->id), $this->donnees())
            ->assertForbidden();

        $this->assertSame(0, $this->fournisseur->contacts()->count());
    }

    public function test_sans_permission_la_suppression_est_refusee(): void
    {
        $contact = ContactFournisseur::factory()->create(['fournisseur_id' => $this->fournisseur->id]);

        $lecteur = $this->utilisateur([
            'catalogue.fournisseurs.index', 'catalogue.contacts.index',
        ], 'lecteur_suppression');

        $this->actingAs($lecteur)
            ->deleteJson(route('catalogue.contacts.destroy', [$this->fournisseur->id, $contact->id]))
            ->assertForbidden();

        $this->assertModelExists($contact);
    }

    // ── L'onglet dans la fiche ─────────────────────────────────────────────

    public function test_la_fiche_affiche_les_onglets_et_le_compteur(): void
    {
        ContactFournisseur::factory()->count(3)->create(['fournisseur_id' => $this->fournisseur->id]);

        $this->actingAs($this->gestionnaire)
            ->get(route('catalogue.fournisseurs.show', $this->fournisseur->id))
            ->assertOk()
            ->assertSee('onglet-contacts')
            ->assertSee('onglet-articles')
            ->assertSee('onglet-journal')
            ->assertSee('id="compteur-contacts"', false)
            // Le compteur est rendu par le serveur : il est juste dès le
            // premier affichage, sans attendre le chargement de la table.
            ->assertSee('>3</span>', false);
    }

    /** L'onglet n'apparaît pas à qui n'a pas le droit de le consulter. */
    public function test_l_onglet_contacts_est_masque_sans_permission(): void
    {
        $intrus = $this->utilisateur(['catalogue.fournisseurs.index'], 'sans_onglet');

        $this->actingAs($intrus)
            ->get(route('catalogue.fournisseurs.show', $this->fournisseur->id))
            ->assertOk()
            ->assertDontSee('onglet-contacts');
    }
}
