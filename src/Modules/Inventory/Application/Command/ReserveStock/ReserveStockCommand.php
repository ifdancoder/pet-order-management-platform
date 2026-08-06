<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Command\ReserveStock;

use Modules\Inventory\Application\Data\StockRequest;
use Modules\Inventory\Domain\Entity\Reservation;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Reservation> */
final readonly class ReserveStockCommand implements ICommand
{
    /** @param list<StockRequest> $items */
    public function __construct(
        public string $reservationKey,
        public array $items,
    ) {}
}
