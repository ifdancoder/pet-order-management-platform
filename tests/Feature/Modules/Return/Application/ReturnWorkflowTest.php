<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Return\Application\Command\ApproveReturn\ApproveReturnCommand;
use Modules\Return\Application\Command\ReceiveReturn\ReceiveReturnCommand;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnItemModel;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(LazilyRefreshDatabase::class);

it('records refund coordination after an approved return is received', function (): void {
    $return = ReturnRequestModel::factory()->create();
    ReturnItemModel::query()->create([
        'return_request_id' => $return->getKey(),
        'inventory_item_id' => '0199a480-0000-7000-8000-000000000004',
        'quantity' => 1,
        'unit_price_amount' => 1000,
    ]);
    $commands = app(ICommandBus::class);

    $approved = $commands->dispatch(new ApproveReturnCommand($return->getKey()));
    $received = $commands->dispatch(new ReceiveReturnCommand($return->getKey()));

    expect($approved->status())->toBe(ReturnStatus::Approved)
        ->and($received->status())->toBe(ReturnStatus::Received);
    $this->assertDatabaseHas('return_requests', [
        'id' => $return->getKey(),
        'status' => ReturnStatus::Received->value,
    ]);
    $this->assertDatabaseHas('outbox_messages', [
        'aggregate_id' => $return->getKey(),
        'event_name' => 'return.received.v1',
    ]);
});
