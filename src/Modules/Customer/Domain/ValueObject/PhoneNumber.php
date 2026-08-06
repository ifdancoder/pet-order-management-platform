<?php

declare(strict_types=1);

namespace Modules\Customer\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class PhoneNumber implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        if (! preg_match('/^\+[1-9]\d{7,14}$/', $value)) {
            throw new InvalidArgumentException('Phone number must use E.164 format.');
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
