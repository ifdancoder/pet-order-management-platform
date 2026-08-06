<?php

declare(strict_types=1);

namespace Modules\Customer\Application\Command\RegisterCustomer;

use Modules\Customer\Application\Exception\CustomerAlreadyExists;
use Modules\Customer\Application\Port\Out\Identity\ICustomerIdGenerator;
use Modules\Customer\Application\Port\Out\Persistence\ICustomerRepository;
use Modules\Customer\Domain\Entity\Customer;
use Modules\Customer\Domain\ValueObject\CustomerName;
use Modules\Customer\Domain\ValueObject\IdentityUserId;
use Modules\Customer\Domain\ValueObject\PhoneNumber;
use Shared\Application\Port\Out\Transaction\ITransactionManager;

final readonly class RegisterCustomerHandler
{
    public function __construct(
        private ICustomerRepository $customers,
        private ICustomerIdGenerator $customerIds,
        private ITransactionManager $transaction,
    ) {}

    public function __invoke(RegisterCustomerCommand $command): Customer
    {
        return $this->transaction->run(function () use ($command): Customer {
            $identityUserId = new IdentityUserId($command->identityUserId);

            if ($this->customers->existsByIdentityUserId($identityUserId)) {
                throw CustomerAlreadyExists::forIdentity($identityUserId->value());
            }

            $customer = Customer::register(
                id: $this->customerIds->generate(),
                identityUserId: $identityUserId,
                name: new CustomerName($command->givenName, $command->familyName),
                phoneNumber: $command->phoneNumber === null
                    ? null
                    : new PhoneNumber($command->phoneNumber),
            );

            $this->customers->save($customer);

            return $customer;
        });
    }
}
