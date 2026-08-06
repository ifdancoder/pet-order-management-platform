<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Exception\CheckoutRejected;
use Modules\Order\Application\Port\Out\Checkout\IInventoryCheckoutGateway;

final readonly class InventoryAvailableRule implements ICheckoutRule
{
    public function __construct(
        private IInventoryCheckoutGateway $inventory,
    ) {}

    public function check(CheckoutContext $context): void
    {
        if (! $this->inventory->isAvailable($context->items)) {
            throw CheckoutRejected::inventoryUnavailable();
        }
    }
}
