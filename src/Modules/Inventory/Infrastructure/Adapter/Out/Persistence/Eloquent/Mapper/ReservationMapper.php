<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Illuminate\Support\Collection;
use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\Enum\ReservationStatus;
use Modules\Inventory\Domain\ValueObject\InventoryItemId;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;
use Modules\Inventory\Domain\ValueObject\ReservationLine;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationLineModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationModel;

final class ReservationMapper
{
    /** @param Collection<int, ReservationLineModel> $lineModels */
    public function toDomain(
        ReservationModel $reservationModel,
        Collection $lineModels,
    ): Reservation {
        return new Reservation(
            id: new ReservationId($reservationModel->id),
            key: new ReservationKey($reservationModel->reservation_key),
            lines: array_values($lineModels->map(
                static fn (ReservationLineModel $line): ReservationLine => new ReservationLine(
                    new InventoryItemId($line->inventory_item_id),
                    $line->quantity,
                ),
            )->all()),
            status: ReservationStatus::from($reservationModel->status),
        );
    }

    public function mapToModel(
        Reservation $reservation,
        ReservationModel $model,
    ): void {
        $model->id = $reservation->id()->value();
        $model->reservation_key = $reservation->key()->value();
        $model->status = $reservation->status()->value;
    }
}
