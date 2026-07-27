<?php

namespace Modules\Achat\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Achat\Contracts\ParcInfoIntegrationInterface;
use Modules\Achat\Services\ArticleService;
use Modules\Achat\Services\BonCommandeService;
use Modules\Achat\Services\BordereauLivraisonService;
use Modules\Achat\Services\CodeInventaireGeneratorService;
use Modules\Achat\Services\ParcInfoIntegrationService;
use Modules\Achat\Services\StatistiquesService;
use Modules\Achat\Services\WizardValidationService;

class AchatServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Achat';

    protected string $moduleNameLower = 'achat';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerBladeComponents();
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // PATTERNS §11 — Les accès aux autres modules passent par un contrat,
        // ce qui permet de les substituer en test.
        $this->app->bind(ParcInfoIntegrationInterface::class, ParcInfoIntegrationService::class);

        // PATTERNS §6 — Services sans état, enregistrés en singleton.
        $this->app->singleton(ArticleService::class);
        $this->app->singleton(BonCommandeService::class);
        $this->app->singleton(BordereauLivraisonService::class);
        $this->app->singleton(WizardValidationService::class);
        $this->app->singleton(CodeInventaireGeneratorService::class);
        $this->app->singleton(StatistiquesService::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'),
            $this->moduleNameLower
        );

        // Format plat imposé par Core\Services\PermissionService
        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'),
            $this->moduleNameLower.'.permissions'
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/roles.php'),
            $this->moduleNameLower.'.roles'
        );
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
    }

    protected function registerBladeComponents(): void
    {
        Blade::component('achat::shared._badge_statut', 'achat-badge-statut');
        Blade::component('achat::shared._badge_type_article', 'achat-badge-type');
        Blade::component('achat::shared._carte_indicateur', 'achat-carte-indicateur');
        Blade::component('achat::shared._etat_vide', 'achat-etat-vide');
    }

    protected function getPublishableViewPaths(): array
    {
        $paths = [];

        foreach ($this->app['config']->get('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->moduleNameLower)) {
                $paths[] = $path.'/modules/'.$this->moduleNameLower;
            }
        }

        return $paths;
    }

    protected function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->moduleNameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->moduleNameLower);
        } else {
            $this->loadTranslationsFrom(module_path($this->moduleName, 'lang'), $this->moduleNameLower);
        }
    }

    public function provides(): array
    {
        return [
            ParcInfoIntegrationInterface::class,
            ArticleService::class,
            BonCommandeService::class,
            BordereauLivraisonService::class,
            WizardValidationService::class,
            CodeInventaireGeneratorService::class,
            StatistiquesService::class,
        ];
    }
}
