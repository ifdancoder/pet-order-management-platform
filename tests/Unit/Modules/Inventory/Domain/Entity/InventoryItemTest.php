<?php

declare(strict_types=1);

use Modules\Inventory\Domain\Entity\InventoryItem;
use Modules\Inventory\Domain\Exception\InsufficientStock;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\Sku;

function inventoryItem(int $onHand = 5): InventoryItem
{
    return InventoryItem::create(
        id: new InventoryItemId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f30'),
        sku: new Sku('SKU-001'),
        onHand: $onHand,
    );
}

it('reserves and releases available stock', function () {
    $item = inventoryItem();

    $item->reserve(3);

    expect($item->stock()->onHand())->toBe(5)
        ->and($item->stock()->reserved())->toBe(3)
        ->and($item->stock()->available())->toBe(2);

    $item->release(2);

    expect($item->stock()->reserved())->toBe(1)
        ->and($item->stock()->available())->toBe(4);
});

it('rejects a reservation larger than available stock', function () {
    $item = inventoryItem(1);

    $item->reserve(2);
})->throws(InsufficientStock::class);

it('restocks without changing reserved stock', function () {
    $item = inventoryItem(2);
    $item->reserve(1);

    $item->restock(3);

    expect($item->stock()->onHand())->toBe(5)
        ->and($item->stock()->reserved())->toBe(1)
        ->and($item->stock()->available())->toBe(4);
});
