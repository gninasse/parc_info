<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Services\NotificationsAchat;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-22 — la cloche de la barre de navigation.
 *
 * Sans elle, le canal « database » écrirait dans une table que personne ne
 * lit : la moitié du dispositif serait inerte. Ce que cette suite protège :
 *
 *   1. **on ne voit QUE ses propres notifications** — un identifiant deviné
 *      ne doit pas donner accès à celles d'un collègue ;
 *   2. **on ne voit que celles du module Achat** : la table `notifications`
 *      est partagée avec le reste de l'application ;
 *   3. la pastille compte les **non lues**, et retombe quand on lit.
 */
class NotificationsClocheTest extends TestCase
{
    use RefreshDatabase;

    private User $acheteur;

    private User $validateur;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        $this->acheteur = $this->utilisateur(['achat.bons_commande.index'], 'acheteur_cloche');
        // Le tableau de bord sert de page témoin pour vérifier le rendu de
        // la pastille : le validateur doit pouvoir l'ouvrir.
        $this->validateur = $this->utilisateur(
            ['achat.bons_commande.index', 'achat.bons_commande.valider', 'achat.dashboard.view'],
            'validateur_cloche'
        );
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

    /** Produit une vraie notification en base, par le chemin normal. */
    private function notifierLeValidateur(): void
    {
        $article = Article::factory()->create(['prix_indicatif' => 50000]);
        $bon = BonCommande::factory()->create(['created_by' => $this->acheteur->id]);
        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id, 'article_id' => $article->id,
            'quantite' => 2, 'prix_unitaire_ht' => 50000, 'taux_tva' => 18,
        ]);

        app(NotificationsAchat::class)->bonSoumis($bon->refresh(), $this->acheteur);
    }

    public function test_la_cloche_liste_les_notifications_du_destinataire(): void
    {
        $this->notifierLeValidateur();

        $reponse = $this->actingAs($this->validateur)
            ->getJson(route('achat.notifications.index'))
            ->assertOk();

        $this->assertCount(1, $reponse->json('notifications'));
        $this->assertSame(1, $reponse->json('non_lues'));
        $this->assertNotNull($reponse->json('notifications.0.url'));
    }

    /** L'acheteur n'est pas destinataire : sa cloche reste vide. */
    public function test_on_ne_voit_pas_les_notifications_des_autres(): void
    {
        $this->notifierLeValidateur();

        $reponse = $this->actingAs($this->acheteur)
            ->getJson(route('achat.notifications.index'))
            ->assertOk();

        $this->assertSame([], $reponse->json('notifications'));
        $this->assertSame(0, $reponse->json('non_lues'));
    }

    /**
     * La table `notifications` est partagée : une notification d'un autre
     * module ne doit pas apparaître dans la cloche Achat, ni gonfler sa
     * pastille.
     */
    public function test_la_cloche_ignore_les_notifications_des_autres_modules(): void
    {
        $this->notifierLeValidateur();

        $this->validateur->notifications()->create([
            'id' => (string) \Illuminate\Support\Str::uuid(),
            'type' => 'Modules\\AutreModule\\Notifications\\Quelconque',
            'data' => ['titre' => 'Étranger', 'message' => 'Venu d\'ailleurs'],
        ]);

        $reponse = $this->actingAs($this->validateur)
            ->getJson(route('achat.notifications.index'))
            ->assertOk();

        $this->assertCount(1, $reponse->json('notifications'));
        $this->assertSame(1, $reponse->json('non_lues'));
    }

    public function test_marquer_lue_fait_retomber_la_pastille(): void
    {
        $this->notifierLeValidateur();

        $id = $this->validateur->notifications()->first()->id;

        $this->actingAs($this->validateur)
            ->postJson(route('achat.notifications.lue', $id))
            ->assertOk()
            ->assertJsonPath('non_lues', 0);

        $this->assertNotNull($this->validateur->notifications()->first()->read_at);
    }

    /**
     * LE verrou : on passe par la relation de l'utilisateur, donc un
     * identifiant appartenant à autrui est introuvable — et non « marqué lu
     * chez le voisin ».
     */
    public function test_on_ne_peut_pas_marquer_lue_la_notification_d_un_autre(): void
    {
        $this->notifierLeValidateur();

        $id = $this->validateur->notifications()->first()->id;

        $this->actingAs($this->acheteur)
            ->postJson(route('achat.notifications.lue', $id))
            ->assertNotFound();

        // Elle est restée non lue chez son vrai destinataire.
        $this->assertNull($this->validateur->notifications()->first()->read_at);
    }

    public function test_tout_marquer_lu_ne_touche_que_ses_propres_notifications(): void
    {
        $this->notifierLeValidateur();

        $this->actingAs($this->acheteur)
            ->postJson(route('achat.notifications.toutes-lues'))
            ->assertOk();

        // L'acheteur a vidé SA cloche (vide) : celle du validateur est intacte.
        $this->assertNull($this->validateur->notifications()->first()->read_at);
    }

    public function test_la_cloche_est_refusee_sans_acces_au_module(): void
    {
        $intrus = $this->utilisateur([], 'intrus_cloche');

        $this->actingAs($intrus)
            ->getJson(route('achat.notifications.index'))
            ->assertForbidden();
    }

    /**
     * La pastille est rendue par le serveur pour être juste dès le premier
     * affichage. Elle ne doit apparaître que s'il y a effectivement à voir.
     */
    public function test_la_pastille_est_rendue_dans_la_barre(): void
    {
        $this->notifierLeValidateur();

        $this->actingAs($this->validateur)
            ->get(route('achat.dashboard'))
            ->assertOk()
            ->assertSee('id="pastille-notifications"', false)
            ->assertSee('notification(s) non lue(s)');
    }

    public function test_la_pastille_est_masquee_quand_il_n_y_a_rien(): void
    {
        $reponse = $this->actingAs($this->validateur)
            ->get(route('achat.dashboard'))
            ->assertOk();

        // La cloche existe toujours, mais la pastille est masquée.
        $this->assertStringContainsString('id="cloche-achat"', $reponse->getContent());
        $this->assertMatchesRegularExpression(
            '/d-none[^>]*id="pastille-notifications"/',
            $reponse->getContent()
        );
    }
}
