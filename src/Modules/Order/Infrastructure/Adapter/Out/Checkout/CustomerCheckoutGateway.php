<?php

declare(strict_types=1);

namespace Modules\Order\Infrastructure\Adapter\Out\Checkout;

use Modules\Customer\Application\Port\In\ICustomerCheckout;
use Modules\Order\Application\Port\Out\Checkout\ICustomerCheckoutGateway;

final readonly class CustomerCheckoutGateway implements ICustomerCheckoutGateway
{
    public function __construct(
        private ICustomerCheckout $customers,
    ) {}

    public function canOrder(string $customerId): bool
    {
        return $this->customers->canOrder($customerId);
    }
}
