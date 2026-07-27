<?php

namespace Modules\Stock\Providers;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;
use Modules\Stock\Console\SnapshotMensuelCommand;
use Modules\Stock\Contracts\AchatIntegrationInterface;
use Modules\Stock\Contracts\GrhIntegrationInterface;
use Modules\Stock\Contracts\OrganisationIntegrationInterface;
use Modules\Stock\Contracts\ParcInfoIntegrationInterface;
use Modules\Stock\Contracts\StockQueryInterface;
use Modules\Stock\Services\AchatIntegrationService;
use Modules\Stock\Services\AlerteStockService;
use Modules\Stock\Services\EntreeStockService;
use Modules\Stock\Services\FifoService;
use Modules\Stock\Services\GrhIntegrationService;
use Modules\Stock\Services\InventaireService;
use Modules\Stock\Services\MagasinService;
use Modules\Stock\Services\OrganisationIntegrationService;
use Modules\Stock\Services\ParcInfoIntegrationService;
use Modules\Stock\Services\RapportService;
use Modules\Stock\Services\RecalculFifoService;
use Modules\Stock\Services\SnapshotService;
use Modules\Stock\Services\SortieStockService;
use Modules\Stock\Services\StockArticleService;
use Modules\Stock\Services\StockQueryService;
use Modules\Stock\Services\TransfertService;

class StockServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Stock';

    protected string $moduleNameLower = 'stock';

    public function boot(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->moduleName, 'database/migrations'));
        $this->registerCommands();
        $this->registerCommandSchedules();
    }

    protected function registerCommands(): void
    {
        $this->commands([SnapshotMensuelCommand::class]);
    }

    protected function registerCommandSchedules(): void
    {
        // RG-F7-06 — snapshot mensuel le 1er du mois à 00h05.
        $this->callAfterResolving(Schedule::class, function (Schedule $schedule) {
            $schedule->command('stock:snapshot-mensuel')->monthlyOn(1, '00:05');
        });
    }

    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
        $this->app->register(EventServiceProvider::class);

        // PATTERNS §11 — Les accès aux autres modules passent par un contrat,
        // ce qui permet de les substituer en test.
        $this->app->bind(AchatIntegrationInterface::class, AchatIntegrationService::class);
        $this->app->bind(ParcInfoIntegrationInterface::class, ParcInfoIntegrationService::class);
        $this->app->bind(GrhIntegrationInterface::class, GrhIntegrationService::class);
        $this->app->bind(OrganisationIntegrationInterface::class, OrganisationIntegrationService::class);

        // Façade de lecture consommée par Achat et ParcInfo (EF-STK-05).
        $this->app->bind(StockQueryInterface::class, StockQueryService::class);

        // PATTERNS §6 — Services sans état, enregistrés en singleton.
        $this->app->singleton(MagasinService::class);
        $this->app->singleton(StockArticleService::class);
        $this->app->singleton(FifoService::class);
        $this->app->singleton(EntreeStockService::class);
        $this->app->singleton(SortieStockService::class);
        $this->app->singleton(TransfertService::class);
        $this->app->singleton(InventaireService::class);
        $this->app->singleton(SnapshotService::class);
        $this->app->singleton(RecalculFifoService::class);
        $this->app->singleton(RapportService::class);
        $this->app->singleton(AlerteStockService::class);
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
    }

    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->moduleNameLower);
        $sourcePath = module_path($this->moduleName, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->moduleNameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->moduleNameLower);
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
            AchatIntegrationInterface::class,
            ParcInfoIntegrationInterface::class,
            GrhIntegrationInterface::class,
            OrganisationIntegrationInterface::class,
            StockQueryInterface::class,
            MagasinService::class,
            StockArticleService::class,
            FifoService::class,
            EntreeStockService::class,
            SortieStockService::class,
            TransfertService::class,
        ];
    }
}
