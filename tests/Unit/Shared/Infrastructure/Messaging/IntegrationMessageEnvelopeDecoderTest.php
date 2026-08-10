<?php

declare(strict_types=1);

use PhpAmqpLib\Message\AMQPMessage;
use Shared\Infrastructure\Messaging\IntegrationMessageEnvelopeDecoder;

it('decodes a versioned integration message envelope', function (): void {
    $message = (new IntegrationMessageEnvelopeDecoder)->decode(new AMQPMessage(
        json_encode([
            'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f70',
            'type' => 'payment.captured.v1',
            'aggregate_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f71',
            'occurred_at' => '2026-09-28T05:00:00+00:00',
            'data' => ['order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f72'],
        ], JSON_THROW_ON_ERROR),
    ));

    expect($message->messageId)->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f70')
        ->and($message->name)->toBe('payment.captured.v1')
        ->and($message->occurredAt->format(DATE_ATOM))->toBe('2026-09-28T05:00:00+00:00')
        ->and($message->data)->toBe([
            'order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f72',
        ])
        ->and($message->correlationId)->toBeNull();
});

it('decodes the correlation id from the AMQP message properties when present', function (): void {
    $message = (new IntegrationMessageEnvelopeDecoder)->decode(new AMQPMessage(
        json_encode([
            'message_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f70',
            'type' => 'payment.captured.v1',
            'aggregate_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f71',
            'occurred_at' => '2026-09-28T05:00:00+00:00',
            'data' => ['order_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f72'],
        ], JSON_THROW_ON_ERROR),
        ['correlation_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f99'],
    ));

    expect($message->correlationId)->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f99');
});

it('rejects an incomplete integration message envelope', function (): void {
    expect(fn () => (new IntegrationMessageEnvelopeDecoder)->decode(
        new AMQPMessage('{"type":"payment.captured.v1"}'),
    ))->toThrow(InvalidArgumentException::class, 'Message envelope is invalid.');
});
