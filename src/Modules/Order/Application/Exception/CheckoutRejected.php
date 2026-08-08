<?php

declare(strict_types=1);

namespace Modules\Order\Application\Exception;

use RuntimeException;

final class CheckoutRejected extends RuntimeException
{
    private function __construct(
        private readonly string $reason,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function customerCannotOrder(): self
    {
        return new self(
            reason: 'customer_cannot_order',
            message: 'Customer is not allowed to place an order.',
        );
    }

    public static function emptyOrder(): self
    {
        return new self(
            reason: 'order_empty',
            message: 'An order must contain at least one item.',
        );
    }

    public static function inventoryUnavailable(): self
    {
        return new self(
            reason: 'inventory_unavailable',
            message: 'Requested inventory is not available.',
        );
    }

    public static function promotionInvalid(): self
    {
        return new self(
            reason: 'promotion_invalid',
            message: 'One or more promotions cannot be applied.',
        );
    }

    public function reason(): string
    {
        return $this->reason;
    }
}
