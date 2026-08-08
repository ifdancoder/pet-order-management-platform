<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Infrastructure\Outbox\LaravelOutboxRepository;
use Shared\Infrastructure\Outbox\LaravelOutboxWriter;

final class OutboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOutboxWriter::class, LaravelOutboxWriter::class);
        $this->app->bind(IOutboxRepository::class, LaravelOutboxRepository::class);
    }
}
