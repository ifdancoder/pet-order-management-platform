<?php

declare(strict_types=1);

namespace Modules\Promotion\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PromotionId implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        )) {
            throw new InvalidArgumentException('Promotion ID must be a valid UUID.');
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
