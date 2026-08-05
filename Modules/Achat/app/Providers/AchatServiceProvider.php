<?php

namespace Modules\Achat\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Modules\Achat\Models\BonCommande;
use Nwidart\Modules\Traits\PathNamespace;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class AchatServiceProvider extends ServiceProvider
{
    use PathNamespace;

    protected string $name = 'Achat';

    protected string $nameLower = 'achat';

    /**
     * Boot the application events.
     */
    public function boot(): void
    {
        $this->registerCommands();
        $this->registerCommandSchedules();
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerViews();
        $this->loadMigrationsFrom(module_path($this->name, 'database/migrations'));
        $this->registerBadgeAValider();
    }

    /**
     * Badge « à valider » de la barre et de la sidebar (SPEC_UX §0.1).
     *
     * Le comptage vit ICI, dans un view composer, et non dans les deux
     * partiels : il y était dupliqué, donc exécuté deux fois par page pour un
     * résultat identique. Il n'est calculé que pour qui détient le visa — le
     * badge ne dit rien d'actionnable aux autres, et une requête inutile reste
     * une requête.
     */
    private function registerBadgeAValider(): void
    {
        View::composer(
            ['achat::layouts.partials.navbar', 'achat::layouts.partials.sidebar'],
            function ($view) {
                $view->with('bonsAValider', once(function () {
                    return auth()->user()?->can('achat.bons_commande.valider')
                        ? BonCommande::query()->aValider()->count()
                        : 0;
                }));
            }
        );
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $this->app->register(EventServiceProvider::class);
        $this->app->register(RouteServiceProvider::class);
    }

    /**
     * Register commands in the format of Command::class
     */
    protected function registerCommands(): void
    {
        // Aucune commande Artisan à ce stade — pas de déclaration morte.
        $this->commands([]);
    }

    /**
     * Register command Schedules.
     */
    protected function registerCommandSchedules(): void {}

    /**
     * Register translations.
     */
    public function registerTranslations(): void
    {
        $langPath = resource_path('lang/modules/'.$this->nameLower);

        if (is_dir($langPath)) {
            $this->loadTranslationsFrom($langPath, $this->nameLower);
            $this->loadJsonTranslationsFrom($langPath);
        } else {
            $this->loadTranslationsFrom(module_path($this->name, 'lang'), $this->nameLower);
            $this->loadJsonTranslationsFrom(module_path($this->name, 'lang'));
        }
    }

    /**
     * Register config.
     */
    protected function registerConfig(): void
    {
        $configPath = module_path($this->name, config('modules.paths.generator.config.path'));

        if (is_dir($configPath)) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($configPath));

            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $config = str_replace($configPath.DIRECTORY_SEPARATOR, '', $file->getPathname());
                    $config_key = str_replace([DIRECTORY_SEPARATOR, '.php'], ['.', ''], $config);
                    $segments = explode('.', $this->nameLower.'.'.$config_key);

                    // Remove duplicated adjacent segments
                    $normalized = [];
                    foreach ($segments as $segment) {
                        if (end($normalized) !== $segment) {
                            $normalized[] = $segment;
                        }
                    }

                    $key = ($config === 'config.php') ? $this->nameLower : implode('.', $normalized);

                    $this->publishes([$file->getPathname() => config_path($config)], 'config');
                    $this->merge_config_from($file->getPathname(), $key);
                }
            }
        }
    }

    /**
     * Merge config from the given path recursively.
     */
    protected function merge_config_from(string $path, string $key): void
    {
        $existing = config($key, []);
        $module_config = require $path;

        config([$key => array_replace_recursive($existing, $module_config)]);
    }

    /**
     * Register views.
     */
    public function registerViews(): void
    {
        $viewPath = resource_path('views/modules/'.$this->nameLower);
        $sourcePath = module_path($this->name, 'resources/views');

        $this->publishes([$sourcePath => $viewPath], ['views', $this->nameLower.'-module-views']);

        $this->loadViewsFrom(array_merge($this->getPublishableViewPaths(), [$sourcePath]), $this->nameLower);

        Blade::componentNamespace(config('modules.namespace').'\\'.$this->name.'\\View\\Components', $this->nameLower);
    }

    /**
     * Get the services provided by the provider.
     */
    public function provides(): array
    {
        return [];
    }

    private function getPublishableViewPaths(): array
    {
        $paths = [];
        foreach (config('view.paths') as $path) {
            if (is_dir($path.'/modules/'.$this->nameLower)) {
                $paths[] = $path.'/modules/'.$this->nameLower;
            }
        }

        return $paths;
    }
}
