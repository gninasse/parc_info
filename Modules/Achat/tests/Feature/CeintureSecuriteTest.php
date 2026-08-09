<?php

namespace Modules\Achat\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\Achat\Database\Seeders\AchatPermissionsSeeder;
use Modules\Catalogue\Database\Seeders\CataloguePermissionsSeeder;
use Modules\Core\Models\User;
use Modules\Stock\Database\Seeders\StockPermissionsSeeder;
use Tests\TestCase;

/**
 * D-19 — la ceinture de sécurité du module, vérifiée par le code lui-même.
 *
 * Les tests fonctionnels prouvent qu'une route donnée refuse un profil donné.
 * Ils ne disent rien de la route qu'on OUBLIERA de protéger dans six mois :
 * c'est toujours celle-là qui pose problème, parce que personne n'écrit un
 * test pour un contrôle qu'il n'a pas conscience d'avoir omis.
 *
 * Ces tests-ci raisonnent donc sur la table de routage entière. Ils sont
 * volontairement génériques : toute route ajoutée au module y passe
 * automatiquement, sans que personne ait à y penser.
 *
 * Quatre garanties :
 *
 *   1. aucune route du module n'est atteignable sans permission ;
 *   2. aucune n'est atteignable sans session (`auth`) ;
 *   3. toute permission exigée par le code est réellement seedée — sinon
 *      elle produit un 403 pour TOUT LE MONDE, administrateur compris, et
 *      la fonctionnalité est morte sans que rien ne le signale ;
 *   4. les routes de lecture ne modifient rien (pas de GET destructeur).
 */
class CeintureSecuriteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CataloguePermissionsSeeder::class);
        $this->seed(StockPermissionsSeeder::class);
        $this->seed(AchatPermissionsSeeder::class);
    }

    /** Les routes du module, hors fermetures anonymes. */
    private function routesDuModule(string $prefixe = 'achat'): array
    {
        $routes = [];

        foreach (Route::getRoutes() as $route) {
            $uri = $route->uri();

            if (! str_starts_with($uri, $prefixe)) {
                continue;
            }

            $action = $route->getActionName();

            if ($action === 'Closure') {
                continue;
            }

            $routes[] = $route;
        }

        return $routes;
    }

    /**
     * Les permissions exigées par une route : celles du routeur ET celles
     * déclarées dans le `middleware()` du contrôleur (`HasMiddleware`), que
     * `gatherMiddleware()` ne voit pas.
     */
    private function permissionsDe($route): array
    {
        $permissions = [];

        foreach ($route->gatherMiddleware() as $middleware) {
            if (is_string($middleware) && str_starts_with($middleware, 'permission:')) {
                $permissions[] = substr($middleware, strlen('permission:'));
            }
        }

        [$classe] = explode('@', $route->getActionName());

        if (class_exists($classe) && is_subclass_of($classe, \Illuminate\Routing\Controllers\HasMiddleware::class)) {
            foreach ($classe::middleware() as $middleware) {
                $valeur = $middleware instanceof \Illuminate\Routing\Controllers\Middleware
                    ? $middleware->middleware
                    : $middleware;

                foreach ((array) $valeur as $un) {
                    if (is_string($un) && str_starts_with($un, 'permission:')) {
                        $permissions[] = substr($un, strlen('permission:'));
                    }
                }
            }
        }

        return array_unique($permissions);
    }

    public function test_aucune_route_du_module_n_est_atteignable_sans_permission(): void
    {
        $nues = [];

        foreach ($this->routesDuModule() as $route) {
            if ($this->permissionsDe($route) === []) {
                $nues[] = $route->methods()[0].' '.$route->uri().' → '.$route->getActionName();
            }
        }

        $this->assertSame([], $nues, "Routes sans permission :\n".implode("\n", $nues));
    }

    public function test_aucune_route_du_module_n_est_atteignable_sans_session(): void
    {
        $publiques = [];

        foreach ($this->routesDuModule() as $route) {
            if (! in_array('auth', $route->gatherMiddleware(), true)) {
                $publiques[] = $route->methods()[0].' '.$route->uri();
            }
        }

        $this->assertSame([], $publiques, "Routes hors authentification :\n".implode("\n", $publiques));
    }

    /**
     * Une permission exigée mais jamais seedée refuse TOUT LE MONDE — y
     * compris l'administrateur — sans qu'aucune erreur ne le signale. La
     * fonctionnalité est morte et personne ne l'apprend.
     */
    public function test_toute_permission_exigee_par_une_route_existe_en_base(): void
    {
        $connues = \Spatie\Permission\Models\Permission::pluck('name')->all();
        $inconnues = [];

        foreach ($this->routesDuModule() as $route) {
            foreach ($this->permissionsDe($route) as $expression) {
                // « a|b » = l'une OU l'autre suffit : chacune doit exister.
                foreach (explode('|', $expression) as $permission) {
                    if (! in_array($permission, $connues, true)) {
                        $inconnues[] = $permission.' (exigée par '.$route->uri().')';
                    }
                }
            }
        }

        $this->assertSame([], array_unique($inconnues), "Permissions exigées mais absentes du seed :\n".implode("\n", array_unique($inconnues)));
    }

    /**
     * Le module compte 20 permissions au SFD §5. Ce test tient le compte :
     * en ajouter une sans l'avoir voulu, ou en perdre une lors d'un
     * remaniement, se voit ici plutôt qu'en production.
     */
    public function test_le_module_declare_exactement_ses_vingt_permissions(): void
    {
        $permissions = \Spatie\Permission\Models\Permission::where('name', 'LIKE', 'achat.%')->pluck('name');

        $this->assertCount(20, $permissions, 'Les 20 permissions du SFD §5 : '.$permissions->implode(', '));
    }

    /**
     * Une route GET ne doit pas modifier l'état : un préchargement de
     * navigateur, un robot d'indexation ou un simple lien partagé
     * déclencheraient l'action. Les verbes le disent, encore faut-il le
     * vérifier — l'erreur se glisse au moment d'un « petit lien pratique ».
     */
    public function test_les_routes_de_lecture_ne_portent_pas_de_verbe_d_action(): void
    {
        $verbesDAction = ['valider', 'annuler', 'cloturer', 'supprimer', 'soumettre',
            'renvoyer', 'reprendre', 'finaliser', 'abandonner', 'reactiver'];

        $suspectes = [];

        foreach ($this->routesDuModule() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue;
            }

            foreach ($verbesDAction as $verbe) {
                if (str_contains($route->uri(), '/'.$verbe)) {
                    $suspectes[] = $route->uri();
                }
            }
        }

        $this->assertSame([], $suspectes, "Actions exposées en GET :\n".implode("\n", $suspectes));
    }

    /**
     * Un utilisateur SANS AUCUNE permission ne doit franchir aucune route du
     * module. C'est la matrice 403 du SFD §9.4, jouée en bloc plutôt que
     * route par route : elle couvre aussi celles qu'on ajoutera demain.
     */
    public function test_un_utilisateur_sans_permission_est_refuse_partout(): void
    {
        $intrus = User::create([
            'name' => 'Sans Droit', 'last_name' => 'Test', 'user_name' => 'sans_droit_ceinture',
            'email' => 'sans-droit-ceinture@example.com', 'password' => bcrypt('password'),
        ]);

        $ouvertes = [];

        foreach ($this->routesDuModule() as $route) {
            if (! in_array('GET', $route->methods(), true)) {
                continue; // les écritures exigent des données valides
            }

            // Une URI à paramètres est instanciée avec des identifiants
            // inexistants : le contrôle de permission passe AVANT la
            // résolution du modèle, donc un 404 signalerait une faille.
            $uri = preg_replace('/\{[^}]+\}/', '999999', $route->uri());

            $reponse = $this->actingAs($intrus)->get('/'.$uri);

            if (! in_array($reponse->getStatusCode(), [403, 302], true)) {
                $ouvertes[] = $route->uri().' → '.$reponse->getStatusCode();
            }
        }

        $this->assertSame([], $ouvertes, "Routes atteintes sans permission :\n".implode("\n", $ouvertes));
    }
}
