<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Shared\Application\Outbox\OutboxPublisher;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Infrastructure\Messaging\RabbitMqMessagePublisher;
use Shared\Infrastructure\Outbox\LaravelOutboxRepository;
use Shared\Infrastructure\Outbox\LaravelOutboxWriter;
use Shared\Infrastructure\Queue\PublishOutboxMessagesJob;

final class OutboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOutboxWriter::class, LaravelOutboxWriter::class);
        $this->app->bind(IOutboxRepository::class, LaravelOutboxRepository::class);
        $this->app->singleton(
            IMessagePublisher::class,
            function (Application $application): IMessagePublisher {
                $config = $application->make(ConfigRepository::class);
                $host = $config->get('messaging.rabbitmq.host');
                $port = $config->get('messaging.rabbitmq.port');
                $user = $config->get('messaging.rabbitmq.user');
                $password = $config->get('messaging.rabbitmq.password');
                $virtualHost = $config->get('messaging.rabbitmq.virtual_host');
                $exchange = $config->get('messaging.rabbitmq.exchange');
                $connectionTimeout = $config->get(
                    'messaging.rabbitmq.connection_timeout_seconds',
                );
                $readWriteTimeout = $config->get(
                    'messaging.rabbitmq.read_write_timeout_seconds',
                );
                $heartbeat = $config->get('messaging.rabbitmq.heartbeat_seconds');
                $confirmTimeout = $config->get(
                    'messaging.rabbitmq.confirm_timeout_seconds',
                );

                if (
                    ! is_string($host) || $host === ''
                    || ! is_int($port) || $port < 1
                    || ! is_string($user) || $user === ''
                    || ! is_string($password)
                    || ! is_string($virtualHost) || $virtualHost === ''
                    || ! is_string($exchange) || $exchange === ''
                    || ! is_float($connectionTimeout) || $connectionTimeout <= 0
                    || ! is_float($readWriteTimeout) || $readWriteTimeout <= 0
                    || ! is_int($heartbeat) || $heartbeat < 1
                    || ! is_float($confirmTimeout) || $confirmTimeout <= 0
                ) {
                    throw new LogicException('RabbitMQ configuration is invalid.');
                }

                return new RabbitMqMessagePublisher(
                    host: $host,
                    port: $port,
                    user: $user,
                    password: $password,
                    virtualHost: $virtualHost,
                    exchange: $exchange,
                    connectionTimeoutSeconds: $connectionTimeout,
                    readWriteTimeoutSeconds: $readWriteTimeout,
                    heartbeatSeconds: $heartbeat,
                    confirmTimeoutSeconds: $confirmTimeout,
                );
            },
        );
        $this->app->bind(
            OutboxPublisher::class,
            function (Application $application): OutboxPublisher {
                $config = $application->make(ConfigRepository::class);
                $batchSize = $config->get('messaging.outbox.batch_size');
                $claimTimeout = $config->get(
                    'messaging.outbox.claim_timeout_seconds',
                );
                $initialRetryDelay = $config->get(
                    'messaging.outbox.initial_retry_delay_seconds',
                );
                $maximumRetryDelay = $config->get(
                    'messaging.outbox.maximum_retry_delay_seconds',
                );

                if (
                    ! is_int($batchSize) || $batchSize < 1
                    || ! is_int($claimTimeout) || $claimTimeout < 1
                    || ! is_int($initialRetryDelay) || $initialRetryDelay < 1
                    || ! is_int($maximumRetryDelay)
                    || $maximumRetryDelay < $initialRetryDelay
                ) {
                    throw new LogicException('Outbox publisher configuration is invalid.');
                }

                return new OutboxPublisher(
                    outbox: $application->make(IOutboxRepository::class),
                    messages: $application->make(IMessagePublisher::class),
                    batchSize: $batchSize,
                    claimTimeoutSeconds: $claimTimeout,
                    initialRetryDelaySeconds: $initialRetryDelay,
                    maximumRetryDelaySeconds: $maximumRetryDelay,
                );
            },
        );
    }

    public function boot(): void
    {
        $this->callAfterResolving(
            Schedule::class,
            static function (Schedule $schedule): void {
                $schedule->job(new PublishOutboxMessagesJob, 'outbox')
                    ->name('outbox-publisher')
                    ->everySecond()
                    ->withoutOverlapping(1)
                    ->onOneServer();
            },
        );
    }
}
