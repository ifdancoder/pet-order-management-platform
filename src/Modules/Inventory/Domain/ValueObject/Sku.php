<?php

declare(strict_types=1);

namespace Modules\Inventory\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class Sku implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match('/^[A-Z0-9][A-Z0-9._-]{0,63}$/', $value)) {
            throw new InvalidArgumentException('SKU has an invalid format.');
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
