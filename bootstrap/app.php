<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        /*
         * 403 nominatives (SPEC_UX §0.5 · DESIGN.md).
         *
         * Par défaut, un refus de permission affiche « Forbidden » : l'utilisateur
         * ignore ce qui lui manque et l'administrateur n'a aucun diagnostic. On
         * enrichit donc la vue d'erreur avec la permission attendue, sous ses deux
         * formes : le libellé métier (ce que la personne voulait faire) et le nom
         * technique (ce qu'il faut accorder dans la matrice des rôles).
         */
        $exceptions->render(function (\Spatie\Permission\Exceptions\UnauthorizedException $e, $request) {
            $requises = collect($e->getRequiredPermissions())
                ->map(fn (string $name) => [
                    'name' => $name,
                    'label' => \Spatie\Permission\Models\Permission::where('name', $name)->value('label') ?? $name,
                ])
                ->values()
                ->all();

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $requises === []
                        ? "Vous n'avez pas l'autorisation d'effectuer cette action."
                        : 'Autorisation requise : '.collect($requises)->pluck('label')->implode(', ').'.',
                    'permissions_requises' => collect($requises)->pluck('name')->all(),
                ], 403);
            }

            return response()->view('errors.403', [
                'permissionsRequises' => $requises,
                'exceptionMessage' => $e->getMessage(),
            ], 403);
        });
    })->create();
