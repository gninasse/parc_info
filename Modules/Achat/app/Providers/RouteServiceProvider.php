<?php

namespace Modules\Achat\Providers;

use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $moduleName = 'Achat';

    protected string $moduleNameLower = 'achat';

    public function boot(): void
    {
        parent::boot();
    }

    public function map(): void
    {
        $this->mapApiRoutes();
        $this->mapWebRoutes();
    }

    protected function mapWebRoutes(): void
    {
        Route::middleware('web')
            ->prefix($this->moduleNameLower)
            ->name($this->moduleNameLower.'.')
            ->group(module_path($this->moduleName, '/routes/web.php'));
    }

    protected function mapApiRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/'.$this->moduleNameLower)
            ->name($this->moduleNameLower.'.')
            ->group(module_path($this->moduleName, '/routes/api.php'));
    }
}
