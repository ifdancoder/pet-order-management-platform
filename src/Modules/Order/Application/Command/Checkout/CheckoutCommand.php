<?php

declare(strict_types=1);

namespace Modules\Order\Application\Command\Checkout;

use Modules\Order\Application\Data\CheckoutResult;
use Modules\Order\Application\Data\OrderItemData;
use Shared\Application\Bus\Command\ICommand;

/** @implements ICommand<CheckoutResult> */
final readonly class CheckoutCommand implements ICommand
{
    /**
     * @param  list<OrderItemData>  $items
     * @param  list<string>  $promotionCodes
     */
    public function __construct(
        public string $idempotencyKey,
        public string $customerId,
        public string $currency,
        public array $items,
        public array $promotionCodes = [],
    ) {}
}
