<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Checkout;

use InvalidArgumentException;
use Modules\Customer\Application\Port\In\ICustomerCheckout;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Enum\CustomerStatus;
use Modules\Customer\Domain\ValueObject\CustomerId;

final readonly class CustomerCheckout implements ICustomerCheckout
{
    public function __construct(
        private ICustomerRepository $customers,
    ) {}

    public function canOrder(string $customerId): bool
    {
        try {
            $customer = $this->customers->findById(new CustomerId($customerId));
        } catch (InvalidArgumentException) {
            return false;
        }

        return $customer?->status() === CustomerStatus::Active;
    }
}
