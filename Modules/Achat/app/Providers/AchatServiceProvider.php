<?php

namespace Modules\Achat\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Modules\Achat\Services\CodeInventaireGeneratorService;
use Modules\Achat\Services\EquipementIntegrationService;
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

        // Enregistrer les services
        $this->app->singleton(WizardValidationService::class);
        $this->app->singleton(EquipementIntegrationService::class);
        $this->app->singleton(CodeInventaireGeneratorService::class);
    }

    protected function registerConfig(): void
    {
        $this->publishes([
            module_path($this->moduleName, 'config/config.php') => config_path($this->moduleNameLower.'.php'),
        ], 'config');

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/config.php'), $this->moduleNameLower
        );

        $this->mergeConfigFrom(
            module_path($this->moduleName, 'config/permissions.php'), $this->moduleNameLower.'.permissions'
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
        Blade::component('achat::shared._badge_statut_bc', 'achat-badge-statut-bc');
        Blade::component('achat::shared._badge_statut_bl', 'achat-badge-statut-bl');
        Blade::component('achat::shared._badge_type_article', 'achat-badge-type-article');
        Blade::component('achat::shared._stats_card', 'achat-stats-card');
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
            WizardValidationService::class,
            EquipementIntegrationService::class,
            CodeInventaireGeneratorService::class,
        ];
    }
}
