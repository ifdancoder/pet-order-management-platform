<?php

declare(strict_types=1);

namespace Modules\Shipping\Application\Command\CreateShipment;

use Modules\Shipping\Domain\Entity\Shipment;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<Shipment> */
final readonly class CreateShipmentCommand implements ICommand
{
    public function __construct(
        public string $orderId,
        public ShippingMethod $method,
        public string $recipientName,
        public string $line1,
        public ?string $line2,
        public string $city,
        public ?string $region,
        public string $postalCode,
        public string $countryCode,
        public int $weightGrams,
        public int $costAmount,
        public string $currency,
    ) {}
}
