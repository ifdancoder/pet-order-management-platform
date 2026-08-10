<?php

declare(strict_types=1);

namespace Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Mapper;

use DateTimeImmutable;
use DateTimeInterface;
use Modules\Return\Domain\Entity\ReturnRequest;
use Modules\Return\Domain\Enum\ReturnStatus;
use Modules\Return\Domain\ValueObject\ReturnId;
use Modules\Return\Domain\ValueObject\ReturnItem;
use Modules\Return\Infrastructure\Adapter\Out\Persistence\Eloquent\Model\ReturnRequestModel;
use RuntimeException;
use Shared\Domain\ValueObject\Money;

final readonly class ReturnRequestMapper
{
    public function toDomain(ReturnRequestModel $model): ReturnRequest
    {
        $requestedAt = $model->getAttribute('requested_at');

        if (! $requestedAt instanceof DateTimeInterface) {
            throw new RuntimeException('Return requested timestamp is invalid.');
        }

        return new ReturnRequest(
            id: new ReturnId($model->id),
            orderId: $model->order_id,
            customerId: $model->customer_id,
            reason: $model->reason,
            currency: $model->currency,
            status: ReturnStatus::from($model->status),
            items: array_values($model->items->map(
                static fn ($item): ReturnItem => new ReturnItem(
                    inventoryItemId: $item->inventory_item_id,
                    quantity: $item->quantity,
                    unitPrice: new Money($item->unit_price_amount, $model->currency),
                ),
            )->all()),
            requestedAt: DateTimeImmutable::createFromInterface($requestedAt),
        );
    }

    /** @return array<string, mixed> */
    public function toAttributes(ReturnRequest $return): array
    {
        return [
            'id' => $return->id()->value(),
            'order_id' => $return->orderId(),
            'customer_id' => $return->customerId(),
            'status' => $return->status()->value,
            'reason' => $return->reason(),
            'currency' => $return->currency(),
            'refund_amount' => $return->refundAmount()->amount(),
            'requested_at' => $return->requestedAt(),
            'created_at' => $return->requestedAt(),
            'updated_at' => $return->requestedAt(),
        ];
    }
}
