<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Context;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Port\Out\Outbox\IOutboxRepository;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

uses(LazilyRefreshDatabase::class);

it('claims the correlation id stored on the outbox message', function (): void {
    Context::add('correlation_id', '018f22e2-7c2a-7a33-8c4c-4ea690ad4f95');
    app(IOutboxWriter::class)->record(new RepositoryTestIntegrationEvent);
    $repository = app(IOutboxRepository::class);

    $claim = $repository->claimBatch(limit: 10, claimTimeoutSeconds: 60);

    expect($claim)->toHaveCount(1)
        ->and($claim[0]->correlationId)->toBe('018f22e2-7c2a-7a33-8c4c-4ea690ad4f95');
});

it('claims pending messages and recovers an expired claim', function (): void {
    $this->travelTo('2026-09-28 04:00:00');
    app(IOutboxWriter::class)->record(new RepositoryTestIntegrationEvent);
    $repository = app(IOutboxRepository::class);

    $firstClaim = $repository->claimBatch(limit: 10, claimTimeoutSeconds: 60);
    $activeClaim = $repository->claimBatch(limit: 10, claimTimeoutSeconds: 60);
    $this->travel(61)->seconds();
    $recoveredClaim = $repository->claimBatch(limit: 10, claimTimeoutSeconds: 60);

    expect($firstClaim)->toHaveCount(1)
        ->and($firstClaim[0]->attempts)->toBe(1)
        ->and($activeClaim)->toBe([])
        ->and($recoveredClaim)->toHaveCount(1)
        ->and($recoveredClaim[0]->messageId)->toBe($firstClaim[0]->messageId)
        ->and($recoveredClaim[0]->claimToken)->not->toBe($firstClaim[0]->claimToken)
        ->and($recoveredClaim[0]->attempts)->toBe(2);
});

it('marks only the currently claimed message as published', function (): void {
    app(IOutboxWriter::class)->record(new RepositoryTestIntegrationEvent);
    $repository = app(IOutboxRepository::class);
    $message = $repository->claimBatch(limit: 1, claimTimeoutSeconds: 60)[0];

    $repository->markPublished($message->messageId, $message->claimToken);

    $this->assertDatabaseHas('outbox_messages', [
        'message_id' => $message->messageId,
        'claim_token' => null,
        'claimed_at' => null,
        'last_error' => null,
    ]);
    expect(
        app('db')->table('outbox_messages')
            ->where('message_id', $message->messageId)
            ->value('published_at'),
    )->not->toBeNull();
});

it('releases a failed message with a retry delay', function (): void {
    $this->travelTo('2026-09-28 04:00:00');
    app(IOutboxWriter::class)->record(new RepositoryTestIntegrationEvent);
    $repository = app(IOutboxRepository::class);
    $message = $repository->claimBatch(limit: 1, claimTimeoutSeconds: 60)[0];

    $repository->release(
        messageId: $message->messageId,
        claimToken: $message->claimToken,
        error: 'RabbitMQ unavailable',
        delaySeconds: 30,
    );

    $this->assertDatabaseHas('outbox_messages', [
        'message_id' => $message->messageId,
        'claim_token' => null,
        'claimed_at' => null,
        'last_error' => 'RabbitMQ unavailable',
        'available_at' => '2026-09-28 04:00:30',
    ]);
});

final readonly class RepositoryTestIntegrationEvent implements IIntegrationEvent
{
    public function name(): string
    {
        return 'test.recorded.v1';
    }

    public function aggregateId(): string
    {
        return 'aggregate-1';
    }

    /** @return array{value: string} */
    public function payload(): array
    {
        return ['value' => 'recorded'];
    }
}
