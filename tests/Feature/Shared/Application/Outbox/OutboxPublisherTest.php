<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Outbox\OutboxMessage;
use Shared\Application\Outbox\OutboxPublisher;
use Shared\Application\Port\Out\Messaging\IMessagePublisher;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

uses(LazilyRefreshDatabase::class);

it('publishes claimed messages and marks them only after success', function (): void {
    $this->travelTo('2026-09-28 05:00:00');
    $writer = app(IOutboxWriter::class);
    $writer->record(new PublisherTestIntegrationEvent('payment.authorized.v1'));
    $writer->record(new PublisherTestIntegrationEvent('payment.failed.v1'));
    $messages = new RecordingMessagePublisher('payment.failed.v1');
    app()->instance(IMessagePublisher::class, $messages);

    $result = app(OutboxPublisher::class)->publishPending();

    expect($result->claimed)->toBe(2)
        ->and($result->published)->toBe(1)
        ->and($result->failed)->toBe(1)
        ->and($messages->publishedEventNames)->toBe(['payment.authorized.v1']);
    expect(DB::table('outbox_messages')->whereNotNull('published_at')->count())
        ->toBe(1);
    $this->assertDatabaseHas('outbox_messages', [
        'event_name' => 'payment.failed.v1',
        'published_at' => null,
        'claimed_at' => null,
        'claim_token' => null,
        'available_at' => '2026-09-28 05:00:05',
    ]);
    expect((string) DB::table('outbox_messages')
        ->where('event_name', 'payment.failed.v1')
        ->value('last_error'))
        ->toContain('Publisher test failure.');
});

final readonly class PublisherTestIntegrationEvent implements IIntegrationEvent
{
    public function __construct(
        private string $eventName,
    ) {}

    public function name(): string
    {
        return $this->eventName;
    }

    public function aggregateId(): string
    {
        return 'payment-1';
    }

    /** @return array{payment_id: string} */
    public function payload(): array
    {
        return ['payment_id' => 'payment-1'];
    }
}

final class RecordingMessagePublisher implements IMessagePublisher
{
    /** @var list<string> */
    public array $publishedEventNames = [];

    public function __construct(
        private readonly string $failingEventName,
    ) {}

    public function publish(OutboxMessage $message): void
    {
        if ($message->eventName === $this->failingEventName) {
            throw new RuntimeException('Publisher test failure.');
        }

        $this->publishedEventNames[] = $message->eventName;
    }
}
