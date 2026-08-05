<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Achat\Database\Seeders\AchatParametresSeeder;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Achat\Models\Parametre;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Socle du module (SFD §5) : permissions, rôles seedés, accès au tableau de
 * bord et navigation. Toute divergence entre `config/permissions.php` et le
 * SFD doit faire échouer la suite.
 */
class AchatSkeletonTest extends TestCase
{
    use RefreshDatabase;

    /** Liste EXACTE du SFD §5. */
    private const PERMISSIONS_SFD_5 = [
        'achat.dashboard.view',
        'achat.bons_commande.index',
        'achat.bons_commande.store',
        'achat.bons_commande.update',
        'achat.bons_commande.destroy',
        'achat.bons_commande.soumettre',
        'achat.bons_commande.valider',
        'achat.bons_commande.annuler',
        'achat.bons_commande.cloturer',
        'achat.bons_commande.regulariser',
        'achat.licences.receptionner',
        'achat.documents.view',
        'achat.documents.store',
        'achat.documents.delete',
        'achat.reliquats.index',
        'achat.rapports.view',
        'achat.rapports.export',
        'achat.rapports.signaux',
        'achat.administration.manage',
        'achat.api.view',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Achat dépend du Catalogue et du Stock (module.json requires)
        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    private function makeUser(array $permissions = [], string $email = 'test@example.com'): User
    {
        $user = User::create([
            'name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user_'.md5($email),
            'email' => $email,
            'password' => bcrypt('password'),
        ]);

        foreach ($permissions as $permission) {
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    // ── Permissions et rôles (SFD §5) ──────────────────────────────────────

    public function test_les_permissions_declarees_correspondent_exactement_au_sfd(): void
    {
        $declarees = array_keys(require module_path('Achat', 'config/permissions.php'));

        sort($declarees);
        $attendues = self::PERMISSIONS_SFD_5;
        sort($attendues);

        $this->assertSame(
            $attendues,
            $declarees,
            'config/permissions.php diverge du SFD §5 (permission manquante ou en trop).'
        );
    }

    public function test_le_seeder_cree_les_permissions_avec_le_module_achat(): void
    {
        $this->assertSame(
            count(self::PERMISSIONS_SFD_5),
            Permission::where('module', 'achat')->whereIn('name', self::PERMISSIONS_SFD_5)->count()
        );
    }

    public function test_les_trois_roles_du_sfd_sont_seedes(): void
    {
        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $role) {
            $this->assertNotNull(Role::where('name', $role)->first(), "Rôle manquant : {$role}");
        }
    }

    /**
     * RGC-11 : la séparation commande/visa est garantie par les permissions.
     * L'Acheteur saisit et soumet, il ne vise pas.
     */
    public function test_l_acheteur_ne_peut_pas_valider(): void
    {
        $acheteur = Role::findByName('Acheteur');

        $this->assertTrue($acheteur->hasPermissionTo('achat.bons_commande.store'));
        $this->assertTrue($acheteur->hasPermissionTo('achat.bons_commande.soumettre'));
        $this->assertFalse($acheteur->hasPermissionTo('achat.bons_commande.valider'));
        $this->assertFalse($acheteur->hasPermissionTo('achat.bons_commande.annuler'));
        $this->assertFalse($acheteur->hasPermissionTo('achat.bons_commande.cloturer'));
    }

    /** Le validateur vise, mais ne crée pas de bon (séparation réciproque). */
    public function test_le_validateur_vise_mais_ne_saisit_pas(): void
    {
        $validateur = Role::findByName('Validateur Achat');

        $this->assertTrue($validateur->hasPermissionTo('achat.bons_commande.valider'));
        $this->assertTrue($validateur->hasPermissionTo('achat.bons_commande.annuler'));
        $this->assertTrue($validateur->hasPermissionTo('achat.bons_commande.cloturer'));
        $this->assertTrue($validateur->hasPermissionTo('achat.documents.delete'));
        $this->assertFalse($validateur->hasPermissionTo('achat.bons_commande.store'));
        $this->assertFalse($validateur->hasPermissionTo('achat.bons_commande.soumettre'));
    }

    public function test_la_consultation_est_en_lecture_seule(): void
    {
        $consultation = Role::findByName('Consultation Achat');

        $ecritures = [
            'achat.bons_commande.store',
            'achat.bons_commande.update',
            'achat.bons_commande.destroy',
            'achat.bons_commande.soumettre',
            'achat.bons_commande.valider',
            'achat.documents.store',
            'achat.administration.manage',
        ];

        foreach ($ecritures as $permission) {
            $this->assertFalse(
                $consultation->hasPermissionTo($permission),
                "Consultation Achat ne doit pas détenir {$permission}."
            );
        }
    }

    /** Le rapport Signaux est réservé : il n'est dans aucun rôle par défaut. */
    public function test_les_signaux_ne_sont_accordes_a_aucun_role_par_defaut(): void
    {
        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $role) {
            $this->assertFalse(
                Role::findByName($role)->hasPermissionTo('achat.rapports.signaux'),
                "{$role} ne doit pas détenir les Signaux (permission d'audit dédiée)."
            );
        }
    }

    public function test_les_roles_achat_recoivent_les_api_catalogue_et_stock(): void
    {
        foreach (['Acheteur', 'Validateur Achat', 'Consultation Achat'] as $role) {
            $this->assertTrue(Role::findByName($role)->hasPermissionTo('catalogue.api.view'));
            $this->assertTrue(Role::findByName($role)->hasPermissionTo('stock.api.view'));
        }
    }

    /** Réciproque du raccordement : Stock consulte les commandes à livrer. */
    public function test_les_roles_stock_recoivent_l_api_achat(): void
    {
        foreach (['Superviseur stock', 'Magasinier', 'Consultation stock'] as $role) {
            $this->assertTrue(
                Role::findByName($role)->hasPermissionTo('achat.api.view'),
                "{$role} doit recevoir achat.api.view (raccordement SFD §5)."
            );
        }
    }

    public function test_le_seeder_de_permissions_est_idempotent(): void
    {
        $this->seed(AchatPermissionsSeeder::class);

        $this->assertSame(
            count(self::PERMISSIONS_SFD_5),
            Permission::where('module', 'achat')->count()
        );
        $this->assertSame(3, Role::whereIn('name', ['Acheteur', 'Validateur Achat', 'Consultation Achat'])->count());
    }

    // ── Paramètres (SFD §6.2) ──────────────────────────────────────────────

    public function test_le_seeder_de_parametres_seme_les_cles_v1_et_est_idempotent(): void
    {
        $this->seed(AchatParametresSeeder::class);
        $this->seed(AchatParametresSeeder::class);

        foreach (array_keys(config('achat.parametres_defaut')) as $cle) {
            $this->assertSame(1, Parametre::where('cle', $cle)->count(), "Paramètre en double ou absent : {$cle}");
        }

        $this->assertSame('BC', Parametre::valeur(Parametre::PREFIXE_NUMEROTATION));
        $this->assertSame(30, Parametre::entier(Parametre::DELAI_ALERTE_RELIQUAT_JOURS));
    }

    /** La porte de régularisation est fermée par défaut (A15/IA-11). */
    public function test_la_regularisation_est_inactive_par_defaut(): void
    {
        $this->seed(AchatParametresSeeder::class);

        $this->assertFalse(Parametre::booleen(Parametre::REGULARISATION_ACTIVE));
    }

    public function test_un_parametre_absent_retombe_sur_le_defaut_de_config(): void
    {
        // Aucun seeder de paramètres : la lecture ne doit pas échouer.
        $this->assertSame('BC', Parametre::valeur(Parametre::PREFIXE_NUMEROTATION));
        $this->assertSame(20, Parametre::entier(Parametre::SEUIL_ECART_PRIX_PCT));
    }

    // ── Accès (contrôle serveur sur toutes les routes) ─────────────────────

    public function test_visiteur_non_authentifie_redirige_vers_login(): void
    {
        $this->get(route('achat.dashboard'))->assertRedirect(route('login'));
    }

    public function test_utilisateur_sans_permission_recoit_403(): void
    {
        $this->actingAs($this->makeUser())
            ->get(route('achat.dashboard'))
            ->assertStatus(403);
    }

    public function test_utilisateur_avec_permission_accede_au_tableau_de_bord(): void
    {
        $this->actingAs($this->makeUser(['achat.dashboard.view']))
            ->get(route('achat.dashboard'))
            ->assertStatus(200)
            ->assertSee('Module Achat');
    }

    public function test_la_navigation_inter_modules_depend_de_la_permission(): void
    {
        $avec = $this->makeUser(['achat.dashboard.view'], 'avec@example.com');
        $this->assertArrayHasKey('achat', $avec->getModuleNavigation());

        $sans = $this->makeUser([], 'sans@example.com');
        $this->assertArrayNotHasKey('achat', $sans->getModuleNavigation());
    }
}
