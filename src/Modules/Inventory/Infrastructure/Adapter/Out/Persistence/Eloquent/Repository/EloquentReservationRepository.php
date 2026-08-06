<?php

declare(strict_types=1);

namespace Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\UniqueConstraintViolationException;
use Modules\Inventory\Application\Exception\ReservationKeyConflict;
use Modules\Inventory\Application\Port\Out\Persistence\IReservationRepository;
use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\ReservationMapper;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationLineModel;
use Modules\Inventory\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReservationModel;

final readonly class EloquentReservationRepository implements IReservationRepository
{
    public function __construct(
        private ReservationMapper $mapper,
    ) {}

    public function save(Reservation $reservation): void
    {
        $model = ReservationModel::query()->find($reservation->id()->value());
        $isNew = $model === null;
        $model ??= new ReservationModel;
        $this->mapper->mapToModel($reservation, $model);

        try {
            $model->save();
        } catch (UniqueConstraintViolationException $exception) {
            throw ReservationKeyConflict::forKey(
                $reservation->key()->value(),
                $exception,
            );
        }

        if (! $isNew) {
            return;
        }

        foreach ($reservation->lines() as $line) {
            ReservationLineModel::query()->create([
                'reservation_id' => $reservation->id()->value(),
                'inventory_item_id' => $line->inventoryItemId()->value(),
                'quantity' => $line->quantity(),
            ]);
        }
    }

    public function findByKey(ReservationKey $reservationKey): ?Reservation
    {
        $model = ReservationModel::query()
            ->where('reservation_key', $reservationKey->value())
            ->first();

        return $model === null ? null : $this->hydrate($model);
    }

    public function findByIdForUpdate(ReservationId $reservationId): ?Reservation
    {
        $model = ReservationModel::query()
            ->whereKey($reservationId->value())
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->hydrate($model);
    }

    private function hydrate(ReservationModel $model): Reservation
    {
        $lines = ReservationLineModel::query()
            ->where('reservation_id', $model->id)
            ->orderBy('inventory_item_id')
            ->get();

        return $this->mapper->toDomain($model, $lines);
    }
}
