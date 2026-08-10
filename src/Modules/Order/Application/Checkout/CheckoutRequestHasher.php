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
            'shipping' => $context->shipping === null ? null : [
                'method' => $context->shipping->method,
                'recipient_name' => $context->shipping->recipientName,
                'line1' => $context->shipping->line1,
                'line2' => $context->shipping->line2,
                'city' => $context->shipping->city,
                'region' => $context->shipping->region,
                'postal_code' => $context->shipping->postalCode,
                'country_code' => $context->shipping->countryCode,
                'weight_grams' => $context->shipping->weightGrams,
            ],
        ], JSON_THROW_ON_ERROR));
    }
}
