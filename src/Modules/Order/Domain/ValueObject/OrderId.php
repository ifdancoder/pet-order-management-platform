<?php

declare(strict_types=1);

namespace Modules\Order\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class OrderId implements Stringable
{
    /** @var non-empty-string */
    private string $value;

    public function __construct(string $value)
    {
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        )) {
            throw new InvalidArgumentException('Invalid order ID.');
        }

        $this->value = $value;
    }

    /** @return non-empty-string */
    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
