<?php

declare(strict_types=1);

namespace Modules\Identity\Infrastructure\Provider\V1;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Application\Port\Out\Identity\IUserIdGenerator;
use Modules\Identity\Application\Port\Out\Persistence\IUserRepository;
use Modules\Identity\Application\Port\Out\Security\IPasswordHasher;
use Modules\Identity\Application\Port\Out\Transaction\ITransactionManager;
use Modules\Identity\Infrastructure\Adapter\Out\Identity\LaravelUserIdGenerator;
use Modules\Identity\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentUserRepository;
use Modules\Identity\Infrastructure\Adapter\Out\Security\LaravelPasswordHasher;
use Modules\Identity\Infrastructure\Adapter\Out\Transaction\LaravelTransactionManager;

final class IdentityServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            IUserRepository::class,
            EloquentUserRepository::class,
        );

        $this->app->bind(
            IPasswordHasher::class,
            LaravelPasswordHasher::class,
        );

        $this->app->bind(
            IUserIdGenerator::class,
            LaravelUserIdGenerator::class,
        );

        $this->app->bind(
            ITransactionManager::class,
            LaravelTransactionManager::class,
        );
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
                Route::prefix('identity')
                    ->name('identity.')
                    ->group(function (): void {
                        $this->loadRoutesFrom(dirname(__DIR__, 3).'/Presentation/Http/V1/Routes/user_routes.php');
                    });
            });
    }
}
