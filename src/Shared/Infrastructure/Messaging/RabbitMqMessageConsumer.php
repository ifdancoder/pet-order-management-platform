<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use Psr\Log\LoggerInterface;
use Shared\Application\Messaging\IntegrationMessageConsumer;
use Throwable;

final readonly class RabbitMqMessageConsumer
{
    /**
     * @param  array<string, array{queue: string, bindings: list<string>, dead_letter_exchange: string, prefetch_count: int}>  $consumers
     */
    public function __construct(
        private IntegrationMessageConsumer $messages,
        private IntegrationMessageEnvelopeDecoder $decoder,
        private LoggerInterface $logger,
        private string $host,
        private int $port,
        private string $user,
        private string $password,
        private string $virtualHost,
        private string $exchange,
        private float $connectionTimeoutSeconds,
        private float $readWriteTimeoutSeconds,
        private int $heartbeatSeconds,
        private float $rpcTimeoutSeconds,
        private array $consumers,
    ) {}

    /** @param callable(): bool $shouldContinue */
    public function consume(string $consumerName, callable $shouldContinue): void
    {
        $connection = $this->connection();
        $channel = $connection->channel();
        $consumer = $this->consumer($consumerName);
        $this->declareTopology($channel, $consumer);
        $channel->basic_qos(0, $consumer['prefetch_count'], false);
        $channel->basic_consume(
            $consumer['queue'],
            '',
            false,
            false,
            false,
            false,
            function (AMQPMessage $message) use ($consumerName): void {
                $this->handle($consumerName, $message);
            },
        );

        try {
            while ($channel->is_consuming() && $shouldContinue()) {
                try {
                    $channel->wait(null, false, 1);
                } catch (AMQPTimeoutException) {
                }
            }
        } finally {
            $channel->close();
            $connection->close();
        }
    }

    public function consumeOne(string $consumerName): bool
    {
        $connection = $this->connection();
        $channel = $connection->channel();
        $consumer = $this->consumer($consumerName);
        $this->declareTopology($channel, $consumer);

        try {
            $message = $channel->basic_get($consumer['queue'], false);

            if ($message === null) {
                return false;
            }

            $this->handle($consumerName, $message);

            return true;
        } finally {
            $channel->close();
            $connection->close();
        }
    }

    private function handle(string $consumerName, AMQPMessage $message): void
    {
        try {
            $integrationMessage = $this->decoder->decode($message);
            $this->messages->consume($consumerName, $integrationMessage);
            $message->ack();
        } catch (Throwable $exception) {
            $this->logger->error('Integration message processing failed.', [
                'exception' => $exception,
                'message_id' => $message->has('message_id')
                    ? $message->get('message_id')
                    : null,
                'message_type' => $message->has('type')
                    ? $message->get('type')
                    : null,
                'consumer' => $consumerName,
            ]);
            $message->nack(requeue: false);
        }
    }

    /**
     * @return array{queue: string, bindings: list<string>, dead_letter_exchange: string, prefetch_count: int}
     */
    private function consumer(string $consumerName): array
    {
        return $this->consumers[$consumerName]
            ?? throw new \InvalidArgumentException(sprintf(
                'RabbitMQ consumer "%s" is not configured.',
                $consumerName,
            ));
    }

    /**
     * @param  array{queue: string, bindings: list<string>, dead_letter_exchange: string, prefetch_count: int}  $consumer
     */
    private function declareTopology(AMQPChannel $channel, array $consumer): void
    {
        $deadLetterQueue = $consumer['queue'].'.dead';
        $channel->exchange_declare(
            $this->exchange,
            AMQPExchangeType::TOPIC,
            false,
            true,
            false,
        );
        $channel->exchange_declare(
            $consumer['dead_letter_exchange'],
            AMQPExchangeType::DIRECT,
            false,
            true,
            false,
        );
        $channel->queue_declare(
            $deadLetterQueue,
            false,
            true,
            false,
            false,
        );
        $channel->queue_bind(
            $deadLetterQueue,
            $consumer['dead_letter_exchange'],
            $consumer['queue'],
        );
        $channel->queue_declare(
            $consumer['queue'],
            false,
            true,
            false,
            false,
            false,
            new AMQPTable([
                'x-dead-letter-exchange' => $consumer['dead_letter_exchange'],
                'x-dead-letter-routing-key' => $consumer['queue'],
            ]),
        );

        foreach ($consumer['bindings'] as $binding) {
            $channel->queue_bind($consumer['queue'], $this->exchange, $binding);
        }
    }

    private function connection(): AMQPStreamConnection
    {
        return new AMQPStreamConnection(
            host: $this->host,
            port: $this->port,
            user: $this->user,
            password: $this->password,
            vhost: $this->virtualHost,
            connection_timeout: $this->connectionTimeoutSeconds,
            read_write_timeout: $this->readWriteTimeoutSeconds,
            keepalive: true,
            heartbeat: $this->heartbeatSeconds,
            channel_rpc_timeout: $this->rpcTimeoutSeconds,
        );
    }
}
