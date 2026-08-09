<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\ValueObject;

use Modules\Notification\Domain\Exception\InvalidRecipient;
use Stringable;

final readonly class EmailRecipient implements Stringable
{
    private string $value;

    public function __construct(string $value)
    {
        $normalized = mb_strtolower(trim($value));

        if (
            filter_var($normalized, FILTER_VALIDATE_EMAIL) === false
            || mb_strlen($normalized) > 254
        ) {
            throw InvalidRecipient::email($value);
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
