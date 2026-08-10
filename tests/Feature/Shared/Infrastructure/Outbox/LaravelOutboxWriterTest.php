<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Context;
use Shared\Application\Event\IIntegrationEvent;
use Shared\Application\Port\Out\Outbox\IOutboxWriter;

uses(LazilyRefreshDatabase::class);

it('records the current correlation id from context on the outbox message', function (): void {
    Context::add('correlation_id', '018f22e2-7c2a-7a33-8c4c-4ea690ad4f90');

    app(IOutboxWriter::class)->record(new WriterTestIntegrationEvent);

    $this->assertDatabaseHas('outbox_messages', [
        'aggregate_id' => 'aggregate-1',
        'correlation_id' => '018f22e2-7c2a-7a33-8c4c-4ea690ad4f90',
    ]);
});

it('records a null correlation id when none is present in context', function (): void {
    app(IOutboxWriter::class)->record(new WriterTestIntegrationEvent);

    $this->assertDatabaseHas('outbox_messages', [
        'aggregate_id' => 'aggregate-1',
        'correlation_id' => null,
    ]);
});

final readonly class WriterTestIntegrationEvent implements IIntegrationEvent
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
