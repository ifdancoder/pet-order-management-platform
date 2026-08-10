<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use JsonException;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;
use RuntimeException;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Throwable;

final class RabbitMqMessagePublisher implements IMessagePublisher
{
    private ?AMQPStreamConnection $connection = null;

    private ?AMQPChannel $channel = null;

    public function __construct(
        private readonly string $host,
        private readonly int $port,
        private readonly string $user,
        private readonly string $password,
        private readonly string $virtualHost,
        private readonly string $exchange,
        private readonly float $connectionTimeoutSeconds,
        private readonly float $readWriteTimeoutSeconds,
        private readonly int $heartbeatSeconds,
        private readonly float $confirmTimeoutSeconds,
    ) {}

    /** @throws JsonException */
    public function publish(OutboxMessage $message): void
    {
        $channel = $this->channel();
        $returned = false;
        $channel->set_return_listener(
            static function () use (&$returned): void {
                $returned = true;
            },
        );
        $body = json_encode([
            'message_id' => $message->messageId,
            'type' => $message->eventName,
            'aggregate_id' => $message->aggregateId,
            'occurred_at' => $message->occurredAt->format(DATE_ATOM),
            'data' => $message->payload,
        ], JSON_THROW_ON_ERROR);
        $properties = [
            'app_id' => 'orderflow',
            'content_encoding' => 'utf-8',
            'content_type' => 'application/json',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'message_id' => $message->messageId,
            'timestamp' => $message->occurredAt->getTimestamp(),
            'type' => $message->eventName,
        ];

        if ($message->correlationId !== null) {
            $properties['correlation_id'] = $message->correlationId;
        }

        $amqpMessage = new AMQPMessage($body, $properties);

        try {
            $channel->basic_publish(
                $amqpMessage,
                $this->exchange,
                $message->eventName,
                true,
            );
            $channel->wait_for_pending_acks_returns($this->confirmTimeoutSeconds);
        } catch (Throwable $exception) {
            $this->disconnect();

            throw $exception;
        }

        if ($returned) {
            throw new RuntimeException(sprintf(
                'RabbitMQ did not route message "%s".',
                $message->messageId,
            ));
        }
    }

    public function __destruct()
    {
        $this->disconnect();
    }

    private function channel(): AMQPChannel
    {
        if ($this->channel?->is_open()) {
            return $this->channel;
        }

        $this->connection = new AMQPStreamConnection(
            host: $this->host,
            port: $this->port,
            user: $this->user,
            password: $this->password,
            vhost: $this->virtualHost,
            connection_timeout: $this->connectionTimeoutSeconds,
            read_write_timeout: $this->readWriteTimeoutSeconds,
            keepalive: true,
            heartbeat: $this->heartbeatSeconds,
            channel_rpc_timeout: $this->confirmTimeoutSeconds,
        );
        $this->channel = $this->connection->channel();
        $this->channel->exchange_declare(
            exchange: $this->exchange,
            type: AMQPExchangeType::TOPIC,
            passive: false,
            durable: true,
            auto_delete: false,
        );
        $this->channel->confirm_select();

        return $this->channel;
    }

    private function disconnect(): void
    {
        try {
            if ($this->channel?->is_open()) {
                $this->channel->close();
            }

            if ($this->connection?->isConnected()) {
                $this->connection->close();
            }
        } catch (Throwable) {
        } finally {
            $this->channel = null;
            $this->connection = null;
        }
    }
}
