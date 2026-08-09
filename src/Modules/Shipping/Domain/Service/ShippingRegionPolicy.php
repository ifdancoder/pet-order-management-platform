<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Service;

use InvalidArgumentException;
use Modules\Shipping\Domain\Exception\ShippingMethodUnavailable;
use Modules\Shipping\Domain\ValueObject\ShippingRateRequest;

final readonly class ShippingRegionPolicy
{
    public function __construct(
        private string $domesticCountryCode,
    ) {
        if (! preg_match('/^[A-Z]{2}$/', $domesticCountryCode)) {
            throw new InvalidArgumentException(
                'Domestic country code must be ISO 3166-1 alpha-2.',
            );
        }
    }

    public function requireDomestic(ShippingRateRequest $shipment): void
    {
        if ($shipment->destination()->countryCode() !== $this->domesticCountryCode) {
            $this->unavailable($shipment);
        }
    }

    public function requireInternational(ShippingRateRequest $shipment): void
    {
        if ($shipment->destination()->countryCode() === $this->domesticCountryCode) {
            $this->unavailable($shipment);
        }
    }

    private function unavailable(ShippingRateRequest $shipment): never
    {
        throw ShippingMethodUnavailable::forDestination(
            $shipment->method(),
            $shipment->destination()->countryCode(),
        );
    }
}
