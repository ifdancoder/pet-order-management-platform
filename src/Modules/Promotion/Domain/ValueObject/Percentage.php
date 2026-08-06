<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\ValueObject;

use InvalidArgumentException;

final readonly class Percentage
{
    public function __construct(
        private int $basisPoints,
    ) {
        if ($basisPoints < 1 || $basisPoints > 10_000) {
            throw new InvalidArgumentException(
                'Percentage must contain between 1 and 10000 basis points.',
            );
        }
    }

    public function basisPoints(): int
    {
        return $this->basisPoints;
    }
}
