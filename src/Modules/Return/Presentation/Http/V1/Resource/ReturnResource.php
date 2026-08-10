<?php

declare(strict_types=1);

namespace Modules\Return\Presentation\Http\V1\Resource;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\Return\Domain\Entity\ReturnRequest;

final class ReturnResource extends JsonResource
{
    private ReturnRequest $return;

    public function __construct(ReturnRequest $resource)
    {
        parent::__construct($resource);
        $this->return = $resource;
    }

    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->return->id()->value(),
            'order_id' => $this->return->orderId(),
            'status' => $this->return->status()->value,
            'reason' => $this->return->reason(),
            'refund_amount' => $this->return->refundAmount()->amount(),
            'currency' => $this->return->currency(),
            'requested_at' => $this->return->requestedAt()->format(DATE_ATOM),
            'items' => array_map(
                static fn ($item): array => [
                    'inventory_item_id' => $item->inventoryItemId(),
                    'quantity' => $item->quantity(),
                    'unit_price_amount' => $item->unitPrice()->amount(),
                ],
                $this->return->items(),
            ),
        ];
    }
}
