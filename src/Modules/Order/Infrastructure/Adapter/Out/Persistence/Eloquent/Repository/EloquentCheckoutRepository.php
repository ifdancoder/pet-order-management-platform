<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Modules\Order\Application\Data\CheckoutRecord;
use Modules\Order\Application\Port\Out\Persistence\ICheckoutRepository;
use Modules\Order\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\CheckoutModel;

final readonly class EloquentCheckoutRepository implements ICheckoutRepository
{
    public function claim(string $idempotencyKey, string $requestHash): bool
    {
        return CheckoutModel::query()->insertOrIgnore([
            'idempotency_key' => $idempotencyKey,
            'request_hash' => $requestHash,
            'created_at' => now(),
            'updated_at' => now(),
        ]) === 1;
    }

    public function findByKey(string $idempotencyKey): ?CheckoutRecord
    {
        $model = CheckoutModel::query()->find($idempotencyKey);

        if ($model === null) {
            return null;
        }

        return new CheckoutRecord(
            idempotencyKey: $model->idempotency_key,
            requestHash: $model->request_hash,
            orderId: $model->order_id,
            inventoryReservationId: $model->inventory_reservation_id,
        );
    }

    public function complete(
        string $idempotencyKey,
        string $orderId,
        string $inventoryReservationId,
    ): void {
        CheckoutModel::query()
            ->whereKey($idempotencyKey)
            ->update([
                'order_id' => $orderId,
                'inventory_reservation_id' => $inventoryReservationId,
                'completed_at' => now(),
                'updated_at' => now(),
            ]);
    }
}
