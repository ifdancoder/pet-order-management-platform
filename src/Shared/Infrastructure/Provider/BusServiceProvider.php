<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Provider;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Shared\Application\Bus\Command\ICommandBus;
use Shared\Application\Bus\Query\IQueryBus;
use Shared\Infrastructure\Bus\Command\LaravelCommandBus;
use Shared\Infrastructure\Bus\Command\LoggingCommandMiddleware;
use Shared\Infrastructure\Bus\HandlerInvoker;
use Shared\Infrastructure\Bus\HandlerRegistry;
use Shared\Infrastructure\Bus\Query\LaravelQueryBus;

final class BusServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(HandlerRegistry::class);
        $this->app->singleton(HandlerInvoker::class);

        $this->app->singleton(
            ICommandBus::class,
            fn (Application $application): ICommandBus => new LaravelCommandBus(
                invoker: $application->make(HandlerInvoker::class),
                middleware: [
                    $application->make(LoggingCommandMiddleware::class),
                ],
            ),
        );

        $this->app->singleton(
            IQueryBus::class,
            fn (Application $application): IQueryBus => new LaravelQueryBus(
                $application->make(HandlerInvoker::class),
            ),
        );
    }
}
