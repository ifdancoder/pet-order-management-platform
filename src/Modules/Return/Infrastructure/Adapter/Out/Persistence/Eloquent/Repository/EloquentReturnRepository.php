<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Repository;

use Illuminate\Database\DatabaseManager;
use Modules\Return\Application\Port\Out\Persistence\IReturnRepository;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\ValueObject\ReturnId;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper\ReturnRequestMapper;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;

final readonly class EloquentReturnRepository implements IReturnRepository
{
    public function __construct(
        private ReturnRequestMapper $mapper,
        private DatabaseManager $database,
    ) {}

    public function claim(ReturnRequest $return): bool
    {
        $inserted = ReturnRequestModel::query()->insertOrIgnore(
            $this->mapper->toAttributes($return),
        );

        if ($inserted !== 1) {
            return false;
        }

        $this->database->table('return_items')->insert(array_map(
            static fn ($item): array => [
                'return_request_id' => $return->id()->value(),
                'inventory_item_id' => $item->inventoryItemId(),
                'quantity' => $item->quantity(),
                'unit_price_amount' => $item->unitPrice()->amount(),
            ],
            $return->items(),
        ));

        return true;
    }

    public function save(ReturnRequest $return): void
    {
        ReturnRequestModel::query()
            ->whereKey($return->id()->value())
            ->update([
                'status' => $return->status()->value,
                'updated_at' => now(),
            ]);
    }

    public function findById(ReturnId $returnId): ?ReturnRequest
    {
        $model = ReturnRequestModel::query()
            ->with('items')
            ->find($returnId->value());

        return $model === null ? null : $this->mapper->toDomain($model);
    }

    public function findByIdForUpdate(ReturnId $returnId): ?ReturnRequest
    {
        $model = ReturnRequestModel::query()
            ->whereKey($returnId->value())
            ->lockForUpdate()
            ->first();

        if ($model === null) {
            return null;
        }

        $model->load('items');

        return $this->mapper->toDomain($model);
    }
}
