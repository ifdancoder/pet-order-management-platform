<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PromotionCode implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = strtoupper(trim($value));

        if (! preg_match('/^[A-Z0-9_-]{3,32}$/', $normalized)) {
            throw new InvalidArgumentException(
                'Promotion code must contain 3 to 32 letters, digits, underscores, or hyphens.',
            );
        }

        $this->value = $normalized;
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
