<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Exception;

use DomainException;
use Modules\Shipping\Domain\Enum\ShippingMethod;

final class ShippingStrategyNotFound extends DomainException
{
    public static function forMethod(ShippingMethod $method): self
    {
        return new self(sprintf(
            'No shipping cost strategy is registered for method "%s".',
            $method->value,
        ));
    }
}
