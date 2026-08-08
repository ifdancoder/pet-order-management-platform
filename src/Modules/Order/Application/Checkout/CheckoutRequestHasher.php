<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout;

use JsonException;
use Modules\Order\Application\Data\OrderItemData;

final readonly class CheckoutRequestHasher
{
    /** @throws JsonException */
    public function hash(CheckoutContext $context): string
    {
        $items = array_map(
            static fn (OrderItemData $item): array => [
                'inventory_item_id' => $item->inventoryItemId,
                'quantity' => $item->quantity,
                'sku' => $item->sku,
                'unit_price_amount' => $item->unitPriceAmount,
            ],
            $context->items,
        );
        usort(
            $items,
            static fn (array $left, array $right): int => $left['inventory_item_id'] <=> $right['inventory_item_id'],
        );

        return hash('sha256', json_encode([
            'currency' => $context->currency,
            'customer_id' => $context->customerId,
            'items' => $items,
            'promotion_codes' => $context->promotionCodes,
        ], JSON_THROW_ON_ERROR));
    }
}
