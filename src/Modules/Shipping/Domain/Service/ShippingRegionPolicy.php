<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Service;

use InvalidArgumentException;
use Modules\Shipping\Domain\Exception\ShippingMethodUnavailable;
use Modules\Shipping\Domain\ValueObject\Shipment;

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

    public function requireDomestic(Shipment $shipment): void
    {
        if ($shipment->destination()->countryCode() !== $this->domesticCountryCode) {
            $this->unavailable($shipment);
        }
    }

    public function requireInternational(Shipment $shipment): void
    {
        if ($shipment->destination()->countryCode() === $this->domesticCountryCode) {
            $this->unavailable($shipment);
        }
    }

    private function unavailable(Shipment $shipment): never
    {
        throw ShippingMethodUnavailable::forDestination(
            $shipment->method(),
            $shipment->destination()->countryCode(),
        );
    }
}
