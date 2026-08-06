<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Port\Out\Persistence;

use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\IdentityUserId;

interface ICustomerRepository
{
    public function save(Customer $customer): void;

    public function findById(CustomerId $customerId): ?Customer;

    public function findByIdForUpdate(CustomerId $customerId): ?Customer;

    public function findByIdentityUserId(IdentityUserId $identityUserId): ?Customer;

    public function findByIdentityUserIdForUpdate(IdentityUserId $identityUserId): ?Customer;

    public function existsByIdentityUserId(IdentityUserId $identityUserId): bool;
}
