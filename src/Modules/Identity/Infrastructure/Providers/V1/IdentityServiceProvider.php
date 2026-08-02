<?php

namespace Modules\Identity\ServiceProvider;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }

    public function boot(): void
    {
        $this->registerRoutes();
    }

    private function registerRoutes(): void
    {
        Route::middleware('api')
            ->prefix('api/v1')
            ->group(function (): void {
                Route::prefix("identity")
                    ->name('identity.')
                    ->group(function (): void {
                        $this->loadRoutesFrom(dirname(__DIR__, 4) . '/Presentation/Http/V1/Routes/user_routes.php');
                    });
            });
    }
}
