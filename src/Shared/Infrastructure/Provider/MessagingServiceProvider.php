<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Provider;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use LogicException;
use Psr\Log\LoggerInterface;
use Shared\Application\Messaging\IntegrationMessageConsumer;
use Shared\Application\Outbox\OutboxPublisher;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Application\Port\Out\Messaging\IProcessedMessageStore;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;
use Shared\Application\Port\Out\Transaction\ITransactionManager;
use Shared\Infrastructure\Console\ConsumeIntegrationMessagesCommand;
use Shared\Infrastructure\Messaging\IntegrationMessageEnvelopeDecoder;
use Shared\Infrastructure\Messaging\LaravelProcessedMessageStore;
use Shared\Infrastructure\Messaging\RabbitMqMessageConsumer;
use Shared\Infrastructure\Messaging\RabbitMqMessagePublisher;
use Shared\Infrastructure\Outbox\LaravelOutboxRepository;
use Shared\Infrastructure\Outbox\LaravelOutboxWriter;
use Shared\Infrastructure\Queue\PublishOutboxMessagesJob;

final class MessagingServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IOutboxWriter::class, LaravelOutboxWriter::class);
        $this->app->bind(IOutboxRepository::class, LaravelOutboxRepository::class);
        $this->app->bind(
            IProcessedMessageStore::class,
            LaravelProcessedMessageStore::class,
        );
        $this->app->singleton(
            IntegrationMessageConsumer::class,
            fn (Application $application): IntegrationMessageConsumer => new IntegrationMessageConsumer(
                handlers: $application->tagged(IIntegrationMessageHandler::class),
                processedMessages: $application->make(IProcessedMessageStore::class),
                transaction: $application->make(ITransactionManager::class),
            ),
        );
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
        $this->app->singleton(
            RabbitMqMessageConsumer::class,
            function (Application $application): RabbitMqMessageConsumer {
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
                $rpcTimeout = $config->get(
                    'messaging.rabbitmq.confirm_timeout_seconds',
                );
                $consumers = self::rabbitMqConsumers(
                    $config->get('messaging.consumers'),
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
                    || ! is_float($rpcTimeout) || $rpcTimeout <= 0
                ) {
                    throw new LogicException('RabbitMQ consumer configuration is invalid.');
                }

                return new RabbitMqMessageConsumer(
                    messages: $application->make(IntegrationMessageConsumer::class),
                    decoder: $application->make(IntegrationMessageEnvelopeDecoder::class),
                    logger: $application->make(LoggerInterface::class),
                    host: $host,
                    port: $port,
                    user: $user,
                    password: $password,
                    virtualHost: $virtualHost,
                    exchange: $exchange,
                    connectionTimeoutSeconds: $connectionTimeout,
                    readWriteTimeoutSeconds: $readWriteTimeout,
                    heartbeatSeconds: $heartbeat,
                    rpcTimeoutSeconds: $rpcTimeout,
                    consumers: $consumers,
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
                    logger: $application->make(LoggerInterface::class),
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
        if ($this->app->runningInConsole()) {
            $this->commands([ConsumeIntegrationMessagesCommand::class]);
        }

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

    /**
     * @return array<string, array{queue: string, bindings: list<string>, dead_letter_exchange: string, prefetch_count: int}>
     */
    private static function rabbitMqConsumers(mixed $configured): array
    {
        if (! is_array($configured) || $configured === []) {
            throw new LogicException('At least one RabbitMQ consumer must be configured.');
        }

        $consumers = [];

        foreach ($configured as $name => $consumer) {
            if (! is_string($name) || $name === '' || ! is_array($consumer)) {
                throw new LogicException('RabbitMQ consumer configuration is invalid.');
            }

            $queue = $consumer['queue'] ?? null;
            $bindings = $consumer['bindings'] ?? null;
            $deadLetterExchange = $consumer['dead_letter_exchange'] ?? null;
            $prefetchCount = $consumer['prefetch_count'] ?? null;

            if (
                ! is_string($queue) || $queue === ''
                || ! is_array($bindings) || $bindings === []
                || ! is_string($deadLetterExchange) || $deadLetterExchange === ''
                || ! is_int($prefetchCount) || $prefetchCount < 1
            ) {
                throw new LogicException(sprintf(
                    'RabbitMQ consumer "%s" configuration is invalid.',
                    $name,
                ));
            }

            foreach ($bindings as $binding) {
                if (! is_string($binding) || $binding === '') {
                    throw new LogicException(sprintf(
                        'RabbitMQ consumer "%s" binding is invalid.',
                        $name,
                    ));
                }
            }

            /** @var list<string> $bindings */
            $bindings = array_values($bindings);
            $consumers[$name] = [
                'queue' => $queue,
                'bindings' => $bindings,
                'dead_letter_exchange' => $deadLetterExchange,
                'prefetch_count' => $prefetchCount,
            ];
        }

        return $consumers;
    }
}
