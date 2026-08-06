<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\AddAddress;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Identity\IAddressIdGenerator;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class AddAddressHandler
{
    public function __construct(
        private ICustomerRepository $customers,
        private IAddressIdGenerator $addressIds,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(AddAddressCommand $command): Customer
    {
        return $this->transaction->run(function () use ($command): Customer {
            $customer = $this->customers->findByIdForUpdate(
                new CustomerId($command->customerId),
            ) ?? throw CustomerNotFound::withId($command->customerId);

            $customer->addAddress(
                $this->addressIds->generate(),
                $command->address->toPostalAddress(),
                $command->makeDefault,
            );
            $this->customers->save($customer);

            return $customer;
        });
    }
}
