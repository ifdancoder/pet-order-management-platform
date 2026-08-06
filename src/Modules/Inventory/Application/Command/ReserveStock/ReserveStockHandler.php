<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\ReserveStock;

use Modules\Inventory\Application\Data\StockRequest;
use Modules\Inventory\Application\Exception\InventoryItemNotFound;
use Modules\Inventory\Application\Exception\ReservationKeyConflict;
use Modules\Inventory\Application\Port\Out\Identity\IReservationIdGenerator;
use Modules\Inventory\Application\Port\Out\Persistence\IInventoryItemRepository;
use Modules\Inventory\Application\Port\Out\Persistence\IReservationRepository;
use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;
use Modules\Inventory\Domain\ValueObject\ReservationLine;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class ReserveStockHandler
{
    public function __construct(
        private IInventoryItemRepository $inventoryItems,
        private IReservationRepository $reservations,
        private IReservationIdGenerator $reservationIds,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(ReserveStockCommand $command): Reservation
    {
        $reservationKey = new ReservationKey($command->reservationKey);
        $lines = array_map(
            static fn (StockRequest $request): ReservationLine => $request->toReservationLine(),
            $command->items,
        );

        return $this->transaction->run(
            function () use ($reservationKey, $lines): Reservation {
                $existingReservation = $this->reservations->findByKey(
                    $reservationKey,
                );

                if ($existingReservation !== null) {
                    return $this->resolveExisting($existingReservation, $lines);
                }

                $reservation = Reservation::create(
                    $this->reservationIds->generate(),
                    $reservationKey,
                    $lines,
                );

                $inventoryItemIds = array_map(
                    static fn (ReservationLine $line): InventoryItemId => $line->inventoryItemId(),
                    $lines,
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

                $existingReservation = $this->reservations->findByKey(
                    $reservationKey,
                );

                if ($existingReservation !== null) {
                    return $this->resolveExisting($existingReservation, $lines);
                }

                foreach ($lines as $line) {
                    $inventoryItem = $itemsById[$line->inventoryItemId()->value()]
                        ?? throw InventoryItemNotFound::withId(
                            $line->inventoryItemId()->value(),
                        );
                    $inventoryItem->reserve($line->quantity());
                }

                $this->inventoryItems->saveMany(array_values($itemsById));
                $this->reservations->save($reservation);

                return $reservation;
            },
        );
    }

    /** @param list<ReservationLine> $lines */
    private function resolveExisting(
        Reservation $reservation,
        array $lines,
    ): Reservation {
        if (! $reservation->hasLines($lines)) {
            throw ReservationKeyConflict::forKey(
                $reservation->key()->value(),
            );
        }

        return $reservation;
    }
}
