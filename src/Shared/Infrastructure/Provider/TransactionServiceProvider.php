<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Infrastructure\Persistence\LaravelTransactionManager;

final class TransactionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            ITransactionManager::class,
            LaravelTransactionManager::class,
        );
    }
}
