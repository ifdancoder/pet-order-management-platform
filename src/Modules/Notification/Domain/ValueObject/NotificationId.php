<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class NotificationId implements Stringable
{
    public function __construct(
        private string $value,
    ) {
        if ($value === '') {
            throw new InvalidArgumentException('Notification ID cannot be empty.');
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
