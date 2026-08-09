<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Shared\Application\Messaging\IntegrationMessage;
use Shared\Application\Messaging\IntegrationMessageConsumer;
use Shared\Application\Port\In\Messaging\IIntegrationMessageHandler;
use Shared\Application\Port\Out\Messaging\IProcessedMessageStore;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

uses(LazilyRefreshDatabase::class);

it('applies the same message only once for a consumer', function (): void {
    $handler = new class implements IIntegrationMessageHandler
    {
        public int $handled = 0;

        public function consumerName(): string
        {
            return 'test.consumer';
        }

        public function messageNames(): array
        {
            return ['test.happened.v1'];
        }

        public function handle(IntegrationMessage $message): void
        {
            $this->handled++;
        }
    };
    $consumer = integrationMessageTestConsumer($handler);
    $message = integrationMessageTestMessage();

    expect($consumer->consume('test.consumer', $message))->toBeTrue()
        ->and($consumer->consume('test.consumer', $message))->toBeFalse()
        ->and($handler->handled)->toBe(1);
    $this->assertDatabaseCount('processed_messages', 1);
});

it('does not record a message when its handler fails', function (): void {
    $handler = new class implements IIntegrationMessageHandler
    {
        public function consumerName(): string
        {
            return 'test.failing-consumer';
        }

        public function messageNames(): array
        {
            return ['test.happened.v1'];
        }

        public function handle(IntegrationMessage $message): void
        {
            throw new RuntimeException('Handler failed.');
        }
    };

    expect(fn (): bool => integrationMessageTestConsumer($handler)
        ->consume('test.failing-consumer', integrationMessageTestMessage()))
        ->toThrow(RuntimeException::class, 'Handler failed.');
    $this->assertDatabaseCount('processed_messages', 0);
});

function integrationMessageTestConsumer(
    IIntegrationMessageHandler $handler,
): IntegrationMessageConsumer {
    return new IntegrationMessageConsumer(
        handlers: [$handler],
        processedMessages: app(IProcessedMessageStore::class),
        transaction: app(ITransactionManager::class),
    );
}

function integrationMessageTestMessage(): IntegrationMessage
{
    return new IntegrationMessage(
        messageId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f60',
        name: 'test.happened.v1',
        aggregateId: '018f22e2-7c2a-7a33-8c4c-4ea690ad4f61',
        occurredAt: new DateTimeImmutable('2026-09-28T05:00:00+00:00'),
        data: [],
    );
}
