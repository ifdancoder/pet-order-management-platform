<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use Modules\Shipping\Domain\Entity\Shipment;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Domain\ValueObject\ShipmentId;
use Modules\Shipping\Domain\ValueObject\ShippingAddress;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;
use RuntimeException;
use Shared\Domain\ValueObject\Money;

final class ShipmentMapper
{
    public function toDomain(ShipmentModel $model): Shipment
    {
        $address = $model->getAttribute('address');

        if (! is_array($address)) {
            throw new RuntimeException('Shipment address must be an object.');
        }

        return new Shipment(
            id: new ShipmentId($model->id),
            orderId: $model->order_id,
            method: ShippingMethod::from($model->method),
            address: new ShippingAddress(
                recipientName: $this->string($address, 'recipient_name'),
                line1: $this->string($address, 'line1'),
                line2: $this->nullableString($address, 'line2'),
                city: $this->string($address, 'city'),
                region: $this->nullableString($address, 'region'),
                postalCode: $this->string($address, 'postal_code'),
                countryCode: $this->string($address, 'country_code'),
            ),
            weightGrams: $model->weight_grams,
            cost: new Money($model->cost_amount, $model->currency),
            status: ShipmentStatus::from($model->status),
            providerShipmentId: $model->provider_shipment_id,
            trackingNumber: $model->tracking_number,
        );
    }

    public function mapToModel(Shipment $shipment, ShipmentModel $model): void
    {
        $model->id = $shipment->id()->value();
        $model->order_id = $shipment->orderId();
        $model->method = $shipment->method()->value;
        $model->setAttribute('address', $shipment->address()->toArray());
        $model->setAttribute('weight_grams', $shipment->weightGrams());
        $model->setAttribute('cost_amount', $shipment->cost()->amount());
        $model->currency = $shipment->cost()->currency();
        $model->status = $shipment->status()->value;
        $model->provider_shipment_id = $shipment->providerShipmentId();
        $model->tracking_number = $shipment->trackingNumber();
    }

    /** @param array<mixed> $address */
    private function string(array $address, string $key): string
    {
        $value = $address[$key] ?? null;

        if (! is_string($value)) {
            throw new RuntimeException('Shipment address is invalid.');
        }

        return $value;
    }

    /** @param array<mixed> $address */
    private function nullableString(array $address, string $key): ?string
    {
        $value = $address[$key] ?? null;

        if ($value !== null && ! is_string($value)) {
            throw new RuntimeException('Shipment address is invalid.');
        }

        return $value;
    }
}
