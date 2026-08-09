<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Queue;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Modules\Shipping\Application\Booking\ShipmentDispatcher;

final class BookPendingShipmentsJob implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 20;

    public int $uniqueFor = 30;

    public function handle(ShipmentDispatcher $dispatcher): void
    {
        $dispatcher->dispatchPending();
    }

    public function uniqueId(): string
    {
        return 'shipment-dispatcher';
    }
}
