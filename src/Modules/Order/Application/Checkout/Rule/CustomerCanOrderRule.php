<?php

declare(strict_types=1);

namespace Modules\Order\Application\Checkout\Rule;

use Modules\Order\Application\Checkout\CheckoutContext;
use Modules\Order\Application\Exception\CheckoutRejected;
use Modules\Order\Application\Port\Out\Checkout\ICustomerCheckoutGateway;

final readonly class CustomerCanOrderRule implements ICheckoutRule
{
    public function __construct(
        private ICustomerCheckoutGateway $customers,
    ) {}

    public function check(CheckoutContext $context): void
    {
        if (! $this->customers->canOrder($context->customerId)) {
            throw CheckoutRejected::customerCannotOrder();
        }
    }
}
