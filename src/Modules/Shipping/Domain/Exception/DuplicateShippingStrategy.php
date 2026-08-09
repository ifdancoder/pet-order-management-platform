<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Exception;

use LogicException;
use Modules\Shipping\Domain\Enum\ShippingMethod;

final class DuplicateShippingStrategy extends LogicException
{
    public static function forMethod(ShippingMethod $method): self
    {
        return new self(sprintf(
            'Multiple shipping cost strategies are registered for method "%s".',
            $method->value,
        ));
    }
}
