<?php

declare(strict_types=1);

namespace Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\Connection;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Shipping\Application\Data\ShipmentBookingAttempt;
use Modules\Shipping\Application\Port\Out\Persistence\IShipmentRepository;
use Modules\Shipping\Domain\Entity\Shipment;
use Modules\Shipping\Domain\Enum\ShipmentStatus;
use Modules\Shipping\Domain\Enum\ShippingMethod;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\ShipmentMapper;
use Modules\Shipping\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ShipmentModel;
use RuntimeException;
use stdClass;

final readonly class EloquentShipmentRepository implements IShipmentRepository
{
    public function __construct(
        private ShipmentMapper $mapper,
        private DatabaseManager $database,
    ) {}

    public function save(Shipment $shipment): void
    {
        $model = ShipmentModel::query()->find($shipment->id()->value())
            ?? new ShipmentModel;
        $this->mapper->mapToModel($shipment, $model);

        if (! $model->exists) {
            $model->setAttribute('available_at', now());
        }

        $model->save();
    }

    public function findByOrderId(string $orderId): ?Shipment
    {
        $model = ShipmentModel::query()->where('order_id', $orderId)->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByOrderIdForUpdate(string $orderId): ?Shipment
    {
        $model = ShipmentModel::query()
            ->where('order_id', $orderId)
            ->lockForUpdate()
            ->first();

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function claimBatch(int $limit, int $claimTimeoutSeconds): array
    {
        if ($limit < 1 || $claimTimeoutSeconds < 1) {
            throw new RuntimeException('Shipment claim configuration must be positive.');
        }

        $connection = $this->connection();

        return $connection->transaction(function () use ($connection, $limit, $claimTimeoutSeconds): array {
            $now = now();
            $query = $connection->table('shipments')
                ->where('status', ShipmentStatus::Pending->value)
                ->where('available_at', '<=', $now)
                ->where(function ($query) use ($now, $claimTimeoutSeconds): void {
                    $query->whereNull('claimed_at')->orWhere(
                        'claimed_at',
                        '<=',
                        $now->copy()->subSeconds($claimTimeoutSeconds),
                    );
                })
                ->orderBy('id')
                ->limit($limit);

            $connection->getDriverName() === 'pgsql'
                ? $query->lock('FOR UPDATE SKIP LOCKED')
                : $query->lockForUpdate();

            /** @var Collection<int, stdClass> $rows */
            $rows = $query->get();

            if ($rows->isEmpty()) {
                return [];
            }

            $claimToken = Str::uuid7()->toString();
            $ids = $rows->map(
                static fn (stdClass $row): string => (string) $row->id,
            )->all();
            $connection->table('shipments')->whereIn('id', $ids)->update([
                'claim_token' => $claimToken,
                'claimed_at' => $now,
                'attempts' => $connection->raw('attempts + 1'),
                'last_error' => null,
                'updated_at' => $now,
            ]);

            return array_values($rows->map(
                fn (stdClass $row): ShipmentBookingAttempt => new ShipmentBookingAttempt(
                    shipmentId: (string) $row->id,
                    claimToken: $claimToken,
                    orderId: (string) $row->order_id,
                    method: ShippingMethod::from((string) $row->method),
                    address: $this->address($row->address),
                    weightGrams: (int) $row->weight_grams,
                    attempts: (int) $row->attempts + 1,
                ),
            )->all());
        });
    }

    public function markBooked(
        string $shipmentId,
        string $claimToken,
        string $providerShipmentId,
        string $trackingNumber,
    ): void {
        $now = now();
        $updated = $this->claimed($shipmentId, $claimToken)->update([
            'status' => ShipmentStatus::Booked->value,
            'provider_shipment_id' => $providerShipmentId,
            'tracking_number' => $trackingNumber,
            'booked_at' => $now,
            'claimed_at' => null,
            'claim_token' => null,
            'last_error' => null,
            'updated_at' => $now,
        ]);

        $this->guardUpdated($updated);
    }

    public function release(
        string $shipmentId,
        string $claimToken,
        string $error,
        int $delaySeconds,
        bool $terminal,
    ): void {
        if ($delaySeconds < 0) {
            throw new RuntimeException('Shipment retry delay cannot be negative.');
        }

        $now = now();
        $updated = $this->claimed($shipmentId, $claimToken)->update([
            'status' => $terminal
                ? ShipmentStatus::Failed->value
                : ShipmentStatus::Pending->value,
            'available_at' => $now->copy()->addSeconds($delaySeconds),
            'claimed_at' => null,
            'claim_token' => null,
            'last_error' => Str::limit($error, 2000, ''),
            'updated_at' => $now,
        ]);

        $this->guardUpdated($updated);
    }

    private function claimed(string $shipmentId, string $claimToken): Builder
    {
        return $this->connection()->table('shipments')
            ->where('id', $shipmentId)
            ->where('claim_token', $claimToken)
            ->where('status', ShipmentStatus::Pending->value);
    }

    private function guardUpdated(int $updated): void
    {
        if ($updated !== 1) {
            throw new RuntimeException('Shipment claim was lost.');
        }
    }

    /** @return array{recipient_name: string, line1: string, line2: ?string, city: string, region: ?string, postal_code: string, country_code: string} */
    private function address(mixed $value): array
    {
        $address = is_string($value) ? json_decode($value, true) : $value;

        if (! is_array($address)) {
            throw new RuntimeException('Shipment address is invalid.');
        }

        return [
            'recipient_name' => (string) ($address['recipient_name'] ?? ''),
            'line1' => (string) ($address['line1'] ?? ''),
            'line2' => isset($address['line2']) ? (string) $address['line2'] : null,
            'city' => (string) ($address['city'] ?? ''),
            'region' => isset($address['region']) ? (string) $address['region'] : null,
            'postal_code' => (string) ($address['postal_code'] ?? ''),
            'country_code' => (string) ($address['country_code'] ?? ''),
        ];
    }

    private function connection(): Connection
    {
        return $this->database->connection();
    }
}
