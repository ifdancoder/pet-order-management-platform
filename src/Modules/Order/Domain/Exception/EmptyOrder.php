<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exception;

use DomainException;

final class EmptyOrder extends DomainException
{
    public static function cannotBePlaced(): self
    {
        return new self('An empty order cannot be placed.');
    }
}
