<?php

declare(strict_types=1);

namespace Modules\Shipping\Domain\Entity;

use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\Exception\InvalidShipmentTransition;
use Modules\Shipping\Domain\ValueObject\ShipmentId;
use Modules\Shipping\Domain\ValueObject\ShippingAddress;
use Shared\Domain\ValueObject\Money;

final class Shipment
{
    public function __construct(
        private readonly ShipmentId $id,
        private readonly string $orderId,
        private readonly ShippingMethod $method,
        private readonly ShippingAddress $address,
        private readonly int $weightGrams,
        private readonly Money $cost,
        private ShipmentStatus $status,
        private ?string $providerShipmentId = null,
        private ?string $trackingNumber = null,
    ) {
        if ($weightGrams < 1) {
            throw new \InvalidArgumentException('Shipment weight must be positive.');
        }
    }

    public static function pending(
        ShipmentId $id,
        string $orderId,
        ShippingMethod $method,
        ShippingAddress $address,
        int $weightGrams,
        Money $cost,
    ): self {
        return new self(
            id: $id,
            orderId: $orderId,
            method: $method,
            address: $address,
            weightGrams: $weightGrams,
            cost: $cost,
            status: ShipmentStatus::Pending,
        );
    }

    public function book(string $providerShipmentId, string $trackingNumber): void
    {
        $this->guardPending('book');

        if (trim($providerShipmentId) === '' || trim($trackingNumber) === '') {
            throw new \InvalidArgumentException(
                'Provider shipment ID and tracking number cannot be blank.',
            );
        }

        $this->providerShipmentId = $providerShipmentId;
        $this->trackingNumber = $trackingNumber;
        $this->status = ShipmentStatus::Booked;
    }

    public function fail(): void
    {
        $this->guardPending('fail');
        $this->status = ShipmentStatus::Failed;
    }

    public function id(): ShipmentId
    {
        return $this->id;
    }

    public function orderId(): string
    {
        return $this->orderId;
    }

    public function method(): ShippingMethod
    {
        return $this->method;
    }

    public function address(): ShippingAddress
    {
        return $this->address;
    }

    public function weightGrams(): int
    {
        return $this->weightGrams;
    }

    public function cost(): Money
    {
        return $this->cost;
    }

    public function status(): ShipmentStatus
    {
        return $this->status;
    }

    public function providerShipmentId(): ?string
    {
        return $this->providerShipmentId;
    }

    public function trackingNumber(): ?string
    {
        return $this->trackingNumber;
    }

    private function guardPending(string $operation): void
    {
        if ($this->status !== ShipmentStatus::Pending) {
            throw InvalidShipmentTransition::from($this->status, $operation);
        }
    }
}
