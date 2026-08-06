<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\ReleaseReservation;

use Modules\Inventory\Domain\Entity\Reservation;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Reservation> */
final readonly class ReleaseReservationCommand implements ICommand
{
    public function __construct(
        public string $reservationId,
    ) {}
}
