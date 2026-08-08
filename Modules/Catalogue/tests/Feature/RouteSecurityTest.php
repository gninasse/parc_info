<?php

namespace Modules\Catalogue\Tests\Feature;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Inventaire de sécurité : AUCUNE route catalogue.* ne doit être servie sans
 * un middleware de permission Spatie couvrant son action.
 */
class RouteSecurityTest extends TestCase
{
    public function test_toutes_les_routes_catalogue_exigent_une_permission(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes())
            ->filter(fn ($route) => str_starts_with((string) $route->getName(), 'catalogue.'));

        $this->assertNotEmpty($routes, 'Aucune route catalogue.* trouvée : inventaire vide suspect.');

        $sansPermission = [];

        foreach ($routes as $route) {
            // 1. Le groupe doit imposer l'authentification
            if (! in_array('auth', $route->gatherMiddleware(), true)) {
                $sansPermission[] = $route->getName().' (pas de middleware auth)';

                continue;
            }

            // 2. Un middleware permission: doit couvrir l'action, soit sur la
            //    route, soit via HasMiddleware du contrôleur
            $permissionRoute = collect($route->gatherMiddleware())
                ->contains(fn ($m) => is_string($m) && str_starts_with($m, 'permission:'));

            if ($permissionRoute) {
                continue;
            }

            $controller = $route->getControllerClass();
            $action = $route->getActionMethod();

            if ($controller === null || ! is_subclass_of($controller, HasMiddleware::class)) {
                $sansPermission[] = $route->getName().' (contrôleur sans HasMiddleware)';

                continue;
            }

            $couverte = collect($controller::middleware())->contains(function ($middleware) use ($action) {
                $definition = $middleware instanceof \Illuminate\Routing\Controllers\Middleware
                    ? $middleware
                    : null;

                if ($definition === null || ! str_starts_with((string) $definition->middleware, 'permission:')) {
                    return false;
                }

                // only vide = toutes les actions ; sinon l'action doit y figurer
                return $definition->only === null || in_array($action, $definition->only, true);
            });

            if (! $couverte) {
                $sansPermission[] = $route->getName()." (action {$action} non couverte par un middleware permission:)";
            }
        }

        $this->assertSame(
            [],
            $sansPermission,
            "Routes catalogue.* sans contrôle de permission :\n - ".implode("\n - ", $sansPermission)
        );
    }
}
