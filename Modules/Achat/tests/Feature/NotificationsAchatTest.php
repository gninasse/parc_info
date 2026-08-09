<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\BonCommande;
use Modules\Achat\Models\LigneCommande;
use Modules\Achat\Notifications\NotificationAchat;
use Modules\Achat\Services\NotificationsAchat;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Catalogue\Models\Article;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-22 — les notifications.
 *
 * Le besoin : les acteurs n'ont plus à guetter. Aujourd'hui le validateur
 * ouvre la liste « au cas où », l'acheteur rappelle le magasin pour savoir
 * si la livraison est arrivée.
 *
 * Ce que cette suite protège, dans l'ordre d'importance :
 *
 *   1. **notifier ne peut jamais faire échouer l'action métier**. Un serveur
 *      de mail injoignable ne doit pas défaire une soumission ou une
 *      réception physique déjà faite ;
 *   2. **les destinataires se déduisent des PERMISSIONS**. Le jour où un
 *      validateur change, rien n'est à modifier — et personne ne continue de
 *      recevoir des bons qu'il n'a plus à viser ;
 *   3. **l'auteur d'une action ne s'auto-notifie pas**. Recevoir un courriel
 *      pour un geste qu'on vient de faire apprend à ignorer les courriels ;
 *   4. **l'opt-out fonctionne**, sinon l'utilisateur noyé filtre dans sa
 *      messagerie et l'information est perdue pour de bon.
 */
class NotificationsAchatTest extends TestCase
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

        $this->acheteur = $this->utilisateur([
            'achat.bons_commande.index', 'achat.bons_commande.store', 'achat.bons_commande.soumettre',
        ], 'acheteur_notif');

        $this->validateur = $this->utilisateur([
            'achat.bons_commande.index', 'achat.bons_commande.valider',
        ], 'validateur_notif');
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

    private function bonAvecLigne(?User $auteur = null): BonCommande
    {
        $article = Article::factory()->create(['prix_indicatif' => 50000]);
        $bon = BonCommande::factory()->create(['created_by' => ($auteur ?? $this->acheteur)->id]);

        LigneCommande::factory()->create([
            'bon_commande_id' => $bon->id,
            'article_id' => $article->id,
            'quantite' => 2,
            'prix_unitaire_ht' => 50000,
            'taux_tva' => 18,
        ]);

        return $bon->refresh();
    }

    // ── Les destinataires se déduisent des permissions ─────────────────────

    public function test_un_bon_soumis_notifie_ceux_qui_peuvent_viser(): void
    {
        Notification::fake();

        $bon = $this->bonAvecLigne();

        app(NotificationsAchat::class)->bonSoumis($bon, $this->acheteur);

        Notification::assertSentTo($this->validateur, NotificationAchat::class);
        // L'auteur ne s'auto-notifie pas : il vient de faire le geste.
        Notification::assertNotSentTo($this->acheteur, NotificationAchat::class);
    }

    /**
     * Le jour où un validateur perd son droit de visa, il cesse de recevoir
     * les bons à viser — sans qu'aucune liste ne soit à mettre à jour.
     */
    public function test_perdre_la_permission_arrete_les_notifications(): void
    {
        Notification::fake();

        $this->validateur->revokePermissionTo('achat.bons_commande.valider');
        $this->validateur->forgetCachedPermissions();

        app(NotificationsAchat::class)->bonSoumis($this->bonAvecLigne(), $this->acheteur);

        Notification::assertNothingSent();
    }

    public function test_un_renvoi_notifie_l_auteur_avec_le_motif(): void
    {
        Notification::fake();

        $bon = $this->bonAvecLigne();

        app(NotificationsAchat::class)->bonRenvoye($bon, 'Prix à renégocier', $this->validateur);

        Notification::assertSentTo(
            $this->acheteur,
            NotificationAchat::class,
            function (NotificationAchat $notification) use ($bon) {
                $contenu = $notification->toDatabase($this->acheteur);

                // Le motif voyage AVEC la notification : sans lui, le
                // destinataire décroche son téléphone.
                return str_contains($contenu['message'], 'Prix à renégocier')
                    && $contenu['bon_id'] === $bon->id;
            }
        );

        Notification::assertNotSentTo($this->validateur, NotificationAchat::class);
    }

    public function test_une_reception_notifie_l_auteur_du_bon(): void
    {
        Notification::fake();

        $bon = $this->bonAvecLigne();

        app(NotificationsAchat::class)->receptionIntegree($bon, 'ENT-2026-0034', 6);

        Notification::assertSentTo($this->acheteur, NotificationAchat::class,
            fn (NotificationAchat $n) => str_contains($n->toDatabase($this->acheteur)['message'], 'ENT-2026-0034'));
    }

    // ── L'opt-out ──────────────────────────────────────────────────────────

    /**
     * Sans opt-out, l'utilisateur noyé crée une règle de filtrage dans sa
     * messagerie — et l'information est perdue pour de bon.
     */
    public function test_couper_les_deux_canaux_arrete_l_envoi(): void
    {
        Notification::fake();

        $service = app(NotificationsAchat::class);
        $service->definirPreference($this->validateur, 'bon_soumis', false, false);

        $service->bonSoumis($this->bonAvecLigne(), $this->acheteur);

        Notification::assertNothingSent();
    }

    /** Couper le courriel laisse la cloche : l'information reste consultable. */
    public function test_couper_le_courriel_laisse_la_cloche(): void
    {
        Notification::fake();

        $service = app(NotificationsAchat::class);
        $service->definirPreference($this->validateur, 'bon_soumis', parMail: false, parCloche: true);

        $service->bonSoumis($this->bonAvecLigne(), $this->acheteur);

        Notification::assertSentTo($this->validateur, NotificationAchat::class,
            fn (NotificationAchat $n) => $n->via($this->validateur) === ['database']);
    }

    /**
     * L'ABSENCE de préférence vaut ACTIF : un nouvel utilisateur reçoit ce
     * qui le concerne sans rien configurer. L'inverse serait piégeux — une
     * notification qu'il faut activer pour exister n'est jamais activée.
     */
    public function test_sans_preference_tout_est_actif(): void
    {
        $preference = app(NotificationsAchat::class)->preference($this->validateur, 'bon_soumis');

        $this->assertTrue($preference['par_mail']);
        $this->assertTrue($preference['par_cloche']);
    }

    // ── Le résumé hebdomadaire ─────────────────────────────────────────────

    /**
     * Un courriel par reliquat noierait le destinataire : un SEUL résumé par
     * personne, quel que soit le nombre de bons.
     */
    public function test_le_resume_groupe_les_bons_d_un_meme_auteur(): void
    {
        Notification::fake();

        foreach (range(1, 3) as $i) {
            $bon = $this->bonAvecLigne();
            $bon->forceFill([
                'statut' => BonCommande::STATUT_VALIDE,
                'numero' => 'BC-2026-100'.$i,
                'valide_le' => now()->subDays(60),
            ])->save();
        }

        $notifies = app(NotificationsAchat::class)->resumeReliquats(30);

        $this->assertSame(1, $notifies, 'Un seul destinataire, donc un seul résumé.');

        Notification::assertSentToTimes($this->acheteur, NotificationAchat::class, 1);
    }

    public function test_le_resume_ignore_les_bons_soldes_et_recents(): void
    {
        Notification::fake();

        // Récent : pas encore en retard.
        $recent = $this->bonAvecLigne();
        $recent->forceFill([
            'statut' => BonCommande::STATUT_VALIDE, 'numero' => 'BC-2026-2001', 'valide_le' => now()->subDays(2),
        ])->save();

        // Ancien mais SOLDÉ : il n'attend plus rien.
        $solde = $this->bonAvecLigne();
        $solde->forceFill([
            'statut' => BonCommande::STATUT_VALIDE, 'numero' => 'BC-2026-2002', 'valide_le' => now()->subDays(90),
        ])->save();
        // Tout est livré : le reste tombe à zéro, la ligne ne compte plus.
        $solde->lignes()->update(['quantite_livree' => 2]);

        $this->assertSame(0, app(NotificationsAchat::class)->resumeReliquats(30));

        Notification::assertNothingSent();
    }

    // ── La robustesse : notifier ne casse jamais une action ────────────────

    /**
     * LE garde-fou. Une notification est un CONFORT : si elle échoue, la
     * soumission reste acquise. Sans cela, un serveur de mail en panne
     * bloquerait le circuit d'engagement de l'établissement.
     */
    public function test_un_echec_de_notification_ne_defait_pas_la_soumission(): void
    {
        $bon = $this->bonAvecLigne();

        // La table des préférences disparaît : la lecture échouera.
        \Illuminate\Support\Facades\Schema::drop('achat_preferences_notification');

        $reponse = $this->actingAs($this->acheteur)
            ->postJson(route('achat.bons-commande.soumettre', $bon->id));

        $reponse->assertOk();
        $this->assertSame(BonCommande::STATUT_SOUMIS, $bon->fresh()->statut);
    }

    // ── L'écran de préférences ─────────────────────────────────────────────

    /**
     * Les préférences sont PERSONNELLES : la permission exigée est celle
     * d'utiliser le module, pas celle de l'administrer. Sinon un utilisateur
     * noyé devrait solliciter un administrateur.
     */
    public function test_l_ecran_est_accessible_a_tout_utilisateur_du_module(): void
    {
        $this->actingAs($this->acheteur)
            ->get(route('achat.preferences-notification'))
            ->assertOk()
            ->assertSee('Mes notifications')
            ->assertSee('Un bon attend mon visa');
    }

    public function test_l_ecran_est_refuse_sans_acces_au_module(): void
    {
        $intrus = $this->utilisateur([], 'intrus_notif');

        $this->actingAs($intrus)
            ->get(route('achat.preferences-notification'))
            ->assertForbidden();
    }

    public function test_une_preference_s_enregistre_carte_par_carte(): void
    {
        $this->actingAs($this->acheteur)
            ->patchJson(route('achat.preferences-notification.modifier', 'bon_renvoye'), [
                'par_mail' => false,
                'par_cloche' => true,
            ])
            ->assertOk();

        $preference = app(NotificationsAchat::class)->preference($this->acheteur, 'bon_renvoye');

        $this->assertFalse($preference['par_mail']);
        $this->assertTrue($preference['par_cloche']);
    }

    public function test_un_type_inconnu_est_refuse(): void
    {
        $this->actingAs($this->acheteur)
            ->patchJson(route('achat.preferences-notification.modifier', 'type_invente'), [
                'par_mail' => true, 'par_cloche' => true,
            ])
            ->assertNotFound();
    }

    /** Un utilisateur ne règle QUE ses propres notifications. */
    public function test_la_preference_est_bien_personnelle(): void
    {
        $this->actingAs($this->acheteur)
            ->patchJson(route('achat.preferences-notification.modifier', 'bon_soumis'), [
                'par_mail' => false, 'par_cloche' => false,
            ])
            ->assertOk();

        // Le validateur, lui, n'a rien changé : ses réglages sont intacts.
        $autre = app(NotificationsAchat::class)->preference($this->validateur, 'bon_soumis');

        $this->assertTrue($autre['par_mail']);
        $this->assertTrue($autre['par_cloche']);
    }
}
