<?php

declare(strict_types=1);

namespace Modules\Notification\Infrastructure\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Modules\Notification\Application\Delivery\NotificationChannelResolver;
use Modules\Notification\Application\Delivery\NotificationDispatcher;
use Modules\Notification\Application\Messaging\UserRegisteredNotificationHandler;
use Modules\Notification\Application\Port\Out\Delivery\INotificationChannel;
use Modules\Notification\Application\Port\Out\Identity\INotificationIdGenerator;
use Modules\Notification\Application\Port\Out\Persistence\INotificationRepository;
use Modules\Notification\Infrastructure\Adapter\Out\Delivery\LaravelEmailNotificationChannel;
use Modules\Notification\Infrastructure\Adapter\Out\Identity\LaravelNotificationIdGenerator;
use Modules\Notification\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository\EloquentNotificationRepository;
use Modules\Notification\Infrastructure\Queue\DeliverPendingNotificationsJob;
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
        $this->app->tag(
            LaravelEmailNotificationChannel::class,
            INotificationChannel::class,
        );
        $this->app->singleton(
            NotificationChannelResolver::class,
            fn (Application $application): NotificationChannelResolver => new NotificationChannelResolver(
                $application->tagged(INotificationChannel::class),
            ),
        );
        $this->app->bind(
            NotificationDispatcher::class,
            function (Application $application): NotificationDispatcher {
                $config = $application->make(ConfigRepository::class);
                $batchSize = $config->get('notification.delivery.batch_size');
                $claimTimeout = $config->get(
                    'notification.delivery.claim_timeout_seconds',
                );
                $maximumAttempts = $config->get(
                    'notification.delivery.maximum_attempts',
                );
                $initialRetryDelay = $config->get(
                    'notification.delivery.initial_retry_delay_seconds',
                );
                $maximumRetryDelay = $config->get(
                    'notification.delivery.maximum_retry_delay_seconds',
                );

                if (
                    ! is_int($batchSize) || $batchSize < 1
                    || ! is_int($claimTimeout) || $claimTimeout < 1
                    || ! is_int($maximumAttempts) || $maximumAttempts < 1
                    || ! is_int($initialRetryDelay) || $initialRetryDelay < 1
                    || ! is_int($maximumRetryDelay)
                    || $maximumRetryDelay < $initialRetryDelay
                ) {
                    throw new LogicException(
                        'Notification delivery configuration is invalid.',
                    );
                }

                return new NotificationDispatcher(
                    notifications: $application->make(INotificationRepository::class),
                    channels: $application->make(NotificationChannelResolver::class),
                    batchSize: $batchSize,
                    claimTimeoutSeconds: $claimTimeout,
                    maximumAttempts: $maximumAttempts,
                    initialRetryDelaySeconds: $initialRetryDelay,
                    maximumRetryDelaySeconds: $maximumRetryDelay,
                );
            },
        );
    }

    public function boot(): void
    {
        $this->loadViewsFrom(
            __DIR__.'/../Mail/View',
            'notification',
        );
        $this->callAfterResolving(
            Schedule::class,
            static function (Schedule $schedule): void {
                $schedule->job(
                    new DeliverPendingNotificationsJob,
                    'notifications',
                )
                    ->name('notification-dispatcher')
                    ->everySecond()
                    ->withoutOverlapping(1)
                    ->onOneServer();
            },
        );
    }
}
