<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\UpdateCustomer;

use Modules\Customer\Application\Exception\CustomerNotFound;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerId;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class UpdateCustomerHandler
{
    public function __construct(
        private ICustomerRepository $customers,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(UpdateCustomerCommand $command): Customer
    {
        return $this->transaction->run(function () use ($command): Customer {
            $customer = $this->customers->findByIdForUpdate(
                new CustomerId($command->customerId),
            ) ?? throw CustomerNotFound::withId($command->customerId);

            $customer->updateProfile(
                new CustomerName($command->givenName, $command->familyName),
                $command->phoneNumber === null
                    ? null
                    : new PhoneNumber($command->phoneNumber),
            );
            $this->customers->save($customer);

            return $customer;
        });
    }
}
