<?php

declare(strict_types=1);

namespace Shared\Infrastructure\Messaging;

use DateTimeImmutable;
use InvalidArgumentException;
use JsonException;
use PhpAmqpLib\Message\AMQPMessage;
use Shared\Application\Messaging\IntegrationMessage;

final class IntegrationMessageEnvelopeDecoder
{
    /** @throws JsonException */
    public function decode(AMQPMessage $message): IntegrationMessage
    {
        $envelope = json_decode(
            $message->getBody(),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        if (! is_array($envelope)) {
            throw new InvalidArgumentException('Message envelope must be an object.');
        }

        $messageId = $envelope['message_id'] ?? null;
        $name = $envelope['type'] ?? null;
        $aggregateId = $envelope['aggregate_id'] ?? null;
        $occurredAt = $envelope['occurred_at'] ?? null;
        $data = $envelope['data'] ?? null;

        if (
            ! is_string($messageId) || $messageId === ''
            || ! is_string($name) || $name === ''
            || ! is_string($aggregateId) || $aggregateId === ''
            || ! is_string($occurredAt) || $occurredAt === ''
            || ! is_array($data)
        ) {
            throw new InvalidArgumentException('Message envelope is invalid.');
        }

        $correlationId = $message->has('correlation_id') ? $message->get('correlation_id') : null;

        return new IntegrationMessage(
            messageId: $messageId,
            name: $name,
            aggregateId: $aggregateId,
            occurredAt: new DateTimeImmutable($occurredAt),
            data: $data,
            correlationId: is_string($correlationId) && $correlationId !== '' ? $correlationId : null,
        );
    }
}
