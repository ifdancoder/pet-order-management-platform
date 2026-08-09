<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Provider;

use Illuminate\Support\ServiceProvider;
use Modules\Notification\Application\Messaging\UserRegisteredNotificationHandler;
use Modules\Notification\Application\Port\Out\Identity\INotificationIdGenerator;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Infrastructure\Adapter\Out\Identity\LaravelNotificationIdGenerator;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentNotificationRepository;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;

final class NotificationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            INotificationIdGenerator::class,
            LaravelNotificationIdGenerator::class,
        );
        $this->app->bind(
            INotificationRepository::class,
            EloquentNotificationRepository::class,
        );
        $this->app->tag(
            UserRegisteredNotificationHandler::class,
            IIntegrationMessageHandler::class,
        );
    }
}
