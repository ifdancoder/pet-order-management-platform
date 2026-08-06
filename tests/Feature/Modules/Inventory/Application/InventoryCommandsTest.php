<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Modules\Inventory\Application\Command\CreateInventoryItem\CreateInventoryItemCommand;
use Modules\Inventory\Application\Command\ReleaseReservation\ReleaseReservationCommand;
use Modules\Inventory\Application\Command\ReserveStock\ReserveStockCommand;
use Modules\Inventory\Application\Command\RestockInventoryItem\RestockInventoryItemCommand;
use Modules\Inventory\Application\Data\StockRequest;
use Modules\Inventory\Application\Exception\ReservationKeyConflict;
use Modules\Inventory\Domain\Exception\InsufficientStock;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\InventoryItemModel;
use Shared\Application\Bus\Command\ICommandBus;

uses(LazilyRefreshDatabase::class);

it('creates and restocks an inventory item', function () {
    $commandBus = app(ICommandBus::class);

    $inventoryItem = $commandBus->dispatch(new CreateInventoryItemCommand(
        sku: 'SKU-001',
        onHand: 2,
    ));
    $restockedItem = $commandBus->dispatch(new RestockInventoryItemCommand(
        inventoryItemId: $inventoryItem->id()->value(),
        quantity: 3,
    ));

    expect($restockedItem->stock()->onHand())->toBe(5)
        ->and($restockedItem->stock()->available())->toBe(5);
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->id()->value(),
        'sku' => 'SKU-001',
        'on_hand' => 5,
        'reserved' => 0,
    ]);
});

it('applies the same reservation key once', function () {
    $inventoryItem = InventoryItemModel::factory()->create([
        'on_hand' => 2,
        'reserved' => 0,
    ]);
    $command = new ReserveStockCommand(
        reservationKey: 'checkout-123',
        items: [new StockRequest($inventoryItem->getKey(), 1)],
    );
    $commandBus = app(ICommandBus::class);

    $firstReservation = $commandBus->dispatch($command);
    $secondReservation = $commandBus->dispatch($command);

    expect($secondReservation->id()->equals($firstReservation->id()))->toBeTrue();
    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'on_hand' => 2,
        'reserved' => 1,
    ]);
    $this->assertDatabaseCount('inventory_reservations', 1);
    $this->assertDatabaseCount('inventory_reservation_lines', 1);
});

it('rejects reuse of a reservation key with another payload', function () {
    $inventoryItem = InventoryItemModel::factory()->create([
        'on_hand' => 3,
        'reserved' => 0,
    ]);
    $commandBus = app(ICommandBus::class);
    $commandBus->dispatch(new ReserveStockCommand(
        reservationKey: 'checkout-123',
        items: [new StockRequest($inventoryItem->getKey(), 1)],
    ));

    expect(fn () => $commandBus->dispatch(new ReserveStockCommand(
        reservationKey: 'checkout-123',
        items: [new StockRequest($inventoryItem->getKey(), 2)],
    )))->toThrow(ReservationKeyConflict::class);

    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'reserved' => 1,
    ]);
    $this->assertDatabaseCount('inventory_reservations', 1);
});

it('rolls back all lines when one item has insufficient stock', function () {
    $availableItem = InventoryItemModel::factory()->create([
        'on_hand' => 2,
        'reserved' => 0,
    ]);
    $unavailableItem = InventoryItemModel::factory()->create([
        'on_hand' => 0,
        'reserved' => 0,
    ]);

    expect(fn () => app(ICommandBus::class)->dispatch(new ReserveStockCommand(
        reservationKey: 'checkout-123',
        items: [
            new StockRequest($availableItem->getKey(), 1),
            new StockRequest($unavailableItem->getKey(), 1),
        ],
    )))->toThrow(InsufficientStock::class);

    $this->assertDatabaseHas('inventory_items', [
        'id' => $availableItem->getKey(),
        'reserved' => 0,
    ]);
    $this->assertDatabaseCount('inventory_reservations', 0);
});

it('releases a reservation only once', function () {
    $inventoryItem = InventoryItemModel::factory()->create([
        'on_hand' => 1,
        'reserved' => 0,
    ]);
    $commandBus = app(ICommandBus::class);
    $reservation = $commandBus->dispatch(new ReserveStockCommand(
        reservationKey: 'checkout-123',
        items: [new StockRequest($inventoryItem->getKey(), 1)],
    ));
    $releaseCommand = new ReleaseReservationCommand(
        $reservation->id()->value(),
    );

    $commandBus->dispatch($releaseCommand);
    $commandBus->dispatch($releaseCommand);

    $this->assertDatabaseHas('inventory_items', [
        'id' => $inventoryItem->getKey(),
        'on_hand' => 1,
        'reserved' => 0,
    ]);
    $this->assertDatabaseHas('inventory_reservations', [
        'id' => $reservation->id()->value(),
        'status' => 'released',
    ]);
});
