<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class ReservationKey implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        $length = mb_strlen($value);

        if ($length < 1 || $length > 128) {
            throw new InvalidArgumentException(
                'Reservation key must contain between 1 and 128 characters.',
            );
        }
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
