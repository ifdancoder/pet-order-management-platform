<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\ValueObject;

use InvalidArgumentException;

final readonly class RefundId
{
    public function __construct(private string $value)
    {
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        )) {
            throw new InvalidArgumentException('Refund ID must be a UUID.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
