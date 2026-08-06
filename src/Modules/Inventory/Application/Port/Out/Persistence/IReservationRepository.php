<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Port\Out\Persistence;

use Modules\Inventory\Domain\Entity\Reservation;
use Modules\Inventory\Domain\ValueObject\ReservationId;
use Modules\Inventory\Domain\ValueObject\ReservationKey;

interface IReservationRepository
{
    public function save(Reservation $reservation): void;

    public function findByKey(ReservationKey $reservationKey): ?Reservation;

    public function findByIdForUpdate(ReservationId $reservationId): ?Reservation;
}
