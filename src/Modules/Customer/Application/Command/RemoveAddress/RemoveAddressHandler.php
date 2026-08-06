<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\RemoveAddress;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\AddressId;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RemoveAddressHandler
{
    public function __construct(
        private ICustomerRepository $customers,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RemoveAddressCommand $command): Customer
    {
        return $this->transaction->run(function () use ($command): Customer {
            $customer = $this->customers->findByIdForUpdate(
                new CustomerId($command->customerId),
            ) ?? throw CustomerNotFound::withId($command->customerId);

            $customer->removeAddress(new AddressId($command->addressId));
            $this->customers->save($customer);

            return $customer;
        });
    }
}
