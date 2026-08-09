<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Exception;

use DomainException;
use Modules\Shipping\Domain\Enum\ShippingMethod;

final class ShippingMethodUnavailable extends DomainException
{
    public static function forDestination(
        ShippingMethod $method,
        string $countryCode,
    ): self {
        return new self(sprintf(
            'Shipping method "%s" is not available for destination "%s".',
            $method->value,
            $countryCode,
        ));
    }
}
