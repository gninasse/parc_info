<?php

namespace Modules\ParcInfo\Http\Controllers\Concerns;

use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

/**
 * Résout la permission d'une action sur une catégorie d'équipement dynamique
 * à partir du nom de la route (parc-info.{ressource}.* → parcinfo.{ressource}.{action}).
 *
 * Repli en cascade : si la permission fine n'est pas déclarée (catégories dont
 * seul .index existe), on exige .index de la ressource, puis
 * parcinfo.equipements.view. Le super-admin passe par Gate::before.
 */
trait AuthorizesDynamicCategory
{
    protected function autoriserCategorie(string $action): void
    {
        $routeName = (string) request()->route()?->getName();
        $ressource = explode('.', preg_replace('/^parc-info\./', '', $routeName), 2)[0] ?: 'equipements';

        $declarees = app(PermissionRegistrar::class)->getPermissions()->pluck('name')->all();

        foreach (["parcinfo.{$ressource}.{$action}", "parcinfo.{$ressource}.index", 'parcinfo.equipements.view'] as $permission) {
            if (in_array($permission, $declarees, true)) {
                Gate::authorize($permission);

                return;
            }
        }

        Gate::authorize('parcinfo.equipements.view');
    }
}
