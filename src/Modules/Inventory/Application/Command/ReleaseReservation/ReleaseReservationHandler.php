<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\ReleaseReservation;

use Modules\Inventory\Application\Exception\InventoryItemNotFound;
use Modules\Inventory\Application\Exception\ReservationNotFound;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Application\Port\Out\Persistence\IReservationRepository;
use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationLine;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class ReleaseReservationHandler
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
        private IReservationRepository $reservations,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(ReleaseReservationCommand $command): Reservation
    {
        return $this->transaction->run(function () use ($command): Reservation {
            $reservation = $this->reservations->findByIdForUpdate(
                new ReservationId($command->reservationId),
            ) ?? throw ReservationNotFound::withId($command->reservationId);

            if (! $reservation->release()) {
                return $reservation;
            }

            $inventoryItemIds = array_map(
                static fn (ReservationLine $line): InventoryItemId => $line->inventoryItemId(),
                $reservation->lines(),
            );
            usort(
                $inventoryItemIds,
                static fn (InventoryItemId $left, InventoryItemId $right): int => $left->value() <=> $right->value(),
            );
            $inventoryItems = $this->inventoryItems->findManyForUpdate(
                $inventoryItemIds,
            );
            $itemsById = [];

            foreach ($inventoryItems as $inventoryItem) {
                $itemsById[$inventoryItem->id()->value()] = $inventoryItem;
            }

            foreach ($reservation->lines() as $line) {
                $inventoryItem = $itemsById[$line->inventoryItemId()->value()]
                    ?? throw InventoryItemNotFound::withId(
                        $line->inventoryItemId()->value(),
                    );
                $inventoryItem->release($line->quantity());
            }

            $this->inventoryItems->saveMany(array_values($itemsById));
            $this->reservations->save($reservation);

            return $reservation;
        });
    }
}
