<?php

declare(strict_types=1);

use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\Enum\ReservationStatus;
use Modules\Inventory\Domain\Exception\DuplicateReservationItem;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;
use Modules\Inventory\Domain\ValueObject\ReservationLine;

function reservationLine(
    string $itemId = '018f22e2-7c2a-7a33-8c4c-4ea690ad4f30',
    int $quantity = 1,
): ReservationLine {
    return new ReservationLine(new InventoryItemId($itemId), $quantity);
}

it('creates an active reservation and releases it once', function () {
    $reservation = Reservation::create(
        id: new ReservationId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f31'),
        key: new ReservationKey('checkout-123'),
        lines: [reservationLine()],
    );

    expect($reservation->status())->toBe(ReservationStatus::Active)
        ->and($reservation->release())->toBeTrue()
        ->and($reservation->status())->toBe(ReservationStatus::Released)
        ->and($reservation->release())->toBeFalse();
});

it('rejects duplicate inventory items', function () {
    Reservation::create(
        id: new ReservationId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f31'),
        key: new ReservationKey('checkout-123'),
        lines: [reservationLine(), reservationLine()],
    );
})->throws(DuplicateReservationItem::class);

it('requires at least one reservation line', function () {
    Reservation::create(
        id: new ReservationId('018f22e2-7c2a-7a33-8c4c-4ea690ad4f31'),
        key: new ReservationKey('checkout-123'),
        lines: [],
    );
})->throws(InvalidArgumentException::class);
