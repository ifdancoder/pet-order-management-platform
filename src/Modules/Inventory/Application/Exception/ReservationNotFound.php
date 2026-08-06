<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Exception;

use RuntimeException;

final class ReservationNotFound extends RuntimeException
{
    public static function withId(string $reservationId): self
    {
        return new self(sprintf(
            'Inventory reservation "%s" was not found.',
            $reservationId,
        ));
    }
}
