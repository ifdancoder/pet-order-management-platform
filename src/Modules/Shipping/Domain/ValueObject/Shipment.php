<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\ValueObject;

use InvalidArgumentException;
use Modules\Shipping\Domain\Enum\ShippingMethod;

final readonly class Shipment
{
    /** @var int<1, max> */
    private int $weightGrams;

    public function __construct(
        private ShippingMethod $method,
        private ShippingDestination $destination,
        int $weightGrams,
        private string $currency,
    ) {
        if ($weightGrams < 1) {
            throw new InvalidArgumentException('Shipment weight must be positive.');
        }

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new InvalidArgumentException(
                'Currency must be an ISO 4217 alpha-3 code.',
            );
        }

        $this->weightGrams = $weightGrams;
    }

    public function method(): ShippingMethod
    {
        return $this->method;
    }

    public function destination(): ShippingDestination
    {
        return $this->destination;
    }

    /** @return int<1, max> */
    public function weightGrams(): int
    {
        return $this->weightGrams;
    }

    public function billableKilograms(): int
    {
        return intdiv($this->weightGrams + 999, 1000);
    }

    public function currency(): string
    {
        return $this->currency;
    }
}
