<?php

declare(strict_types=1);

namespace Modules\Order\Domain\Exception;

use DomainException;
use Modules\Order\Domain\Enum\OrderStatus;

final class InvalidOrderStatusTransition extends DomainException
{
    public static function fromTo(
        OrderStatus $from,
        OrderStatus $to,
    ): self {
        return new self(sprintf(
            'Order cannot transition from %s to %s.',
            $from->value,
            $to->value,
        ));
    }
}
