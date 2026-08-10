<?php

declare(strict_types=1);

namespace Modules\Return\Domain\ValueObject;

use InvalidArgumentException;

final readonly class ReturnId
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[1-8][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $value,
        )) {
            throw new InvalidArgumentException('Return ID must be a UUID.');
        }
    }

    public function value(): string
    {
        return $this->value;
    }
}
