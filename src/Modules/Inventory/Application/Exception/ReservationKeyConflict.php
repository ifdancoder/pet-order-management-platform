<?php

declare(strict_types=1);

namespace Modules\Inventory\Application\Exception;

use RuntimeException;
use Throwable;

final class ReservationKeyConflict extends RuntimeException
{
    public static function forKey(
        string $reservationKey,
        ?Throwable $previous = null,
    ): self {
        return new self(
            sprintf(
                'Reservation key "%s" was already used for another request.',
                $reservationKey,
            ),
            previous: $previous,
        );
    }
}
