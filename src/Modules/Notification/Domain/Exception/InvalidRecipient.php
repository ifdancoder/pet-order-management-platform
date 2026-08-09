<?php

declare(strict_types=1);

namespace Modules\Notification\Domain\Exception;

use DomainException;

final class InvalidRecipient extends DomainException
{
    public static function email(string $value): self
    {
        return new self(sprintf('"%s" is not a valid notification email.', $value));
    }
}
